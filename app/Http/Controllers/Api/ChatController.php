<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageReactionUpdated;
use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendMessageRequest;
use App\Http\Requests\Api\StoreMessageReactionRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Chat\ChatCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * API chat controller consumed by the Inertia frontend.
 *
 * sendMessage  — send a message (member pays if requires_credit is set;
 *                escort sends locked, member pays later to unlock)
 * unlockMessage — member pays credits to reveal a locked message's content
 * messages     — load paginated conversation history with locked content masked
 * addReaction  — add or update a reaction on a message
 * removeReaction — remove a reaction from a message
 */
class ChatController extends Controller
{
    public function __construct(
        private readonly ChatCreditService $creditService,
    ) {}

    /**
     * Send a message in a conversation.
     *
     * Two paid-message models handled here:
     *   A. Member sends with requires_credit=true → member pays at send time
     *      (sender-pays).
     *   B. Escort sends with requires_credit=true → message is locked;
     *      the member (receiver) pays later via unlockMessage (receiver-pays).
     *
     * Free messages (requires_credit = false) are always delivered
     * immediately without any credit check.
     *
     * Supports optional file attachment via multipart/form-data.
     */
    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        $sender = Auth::user();
        $conversation = Conversation::findOrFail($request->integer('conversation_id'));

        if ($conversation->user_one_id !== $sender->id && $conversation->user_two_id !== $sender->id) {
            return response()->json(['message' => 'You are not part of this conversation.'], 403);
        }

        if ($conversation->isBlockedForUser($sender->id)) {
            return response()->json(['message' => 'This conversation is blocked.'], 403);
        }

        $receiverId = $conversation->otherUser($sender->id)->id;

        $requiresCredit = $conversation->is_paid_conversation
            || $request->boolean('requires_credit');

        $payAtSend = $requiresCredit && $sender->isMember();

        if ($payAtSend && ! $this->creditService->canSendMessage($sender, $conversation)) {
            return response()->json(['message' => 'Insufficient credits.'], 422);
        }

        $creditCost = $request->float('credit_cost', $this->creditService->getMessageCost());

        // Handle file attachment before the transaction
        $attachmentData = $this->handleAttachmentUpload($request, $conversation);

        /** @var Message $message */
        $message = DB::transaction(function () use (
            $conversation, $sender, $receiverId, $request, $requiresCredit, $payAtSend, $creditCost, $attachmentData
        ) {
            $type = $attachmentData
                ? $attachmentData['type']
                : $request->input('type', 'text');

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'receiver_id' => $receiverId,
                'client_id' => $request->input('client_id'),
                'message' => $request->input('message'),
                'type' => $type,
                'reply_to_id' => $request->input('reply_to_id'),
                'attachment_path' => $attachmentData['path'] ?? null,
                'attachment_name' => $attachmentData['name'] ?? null,
                'attachment_size' => $attachmentData['size'] ?? null,
                'attachment_mime' => $attachmentData['mime'] ?? null,
                'attachment_meta' => $attachmentData['meta'] ?? null,
                'is_sent' => true,
                'sent_at' => now(),
                'requires_credit' => $requiresCredit,
                'credit_cost' => $creditCost,
                'is_paid' => $payAtSend,
                'payment_verified' => $payAtSend,
            ]);

            if ($payAtSend) {
                $this->creditService->processPaidMessage($sender, $conversation, $message);
            }

            $conversation->updateQuietly(['last_message_at' => now()]);

            return $message;
        });

        broadcast(new NewMessage($message, $conversation))->toOthers();

        $message->load(['sender', 'receiver']);

        return response()->json([
            'data' => $this->formatMessageForUser($message, $sender),
        ], 201);
    }

    /**
     * Unlock a locked message — the authenticated member (receiver) pays
     * credits to reveal the content.
     *
     * POST /api/chat/messages/{message}/unlock
     */
    public function unlockMessage(Message $message): JsonResponse
    {
        $user = Auth::user();

        if ($message->receiver_id !== $user->id) {
            return response()->json(['message' => 'Only the message recipient can unlock it.'], 403);
        }

        if (! $user->isMember() || ! $user->memberProfile) {
            return response()->json(['message' => 'Only members can unlock messages.'], 403);
        }

        if (! $message->requires_credit) {
            return response()->json(['message' => 'This message does not require credit to view.'], 422);
        }

        if ($message->is_paid) {
            return response()->json(['message' => 'This message is already unlocked.'], 422);
        }

        $cost = (float) $message->credit_cost;
        if (! $user->hasSufficientCredits($cost)) {
            return response()->json(['message' => 'Insufficient credits.'], 422);
        }

        $conversation = $message->conversation;

        DB::transaction(function () use ($user, $message, $conversation) {
            $this->creditService->processUnlockPayment($user, $message, $conversation);
        });

        $message->load(['sender', 'receiver']);

        return response()->json([
            'data' => $this->formatMessageForUser($message, $user),
        ]);
    }

    /**
     * Get paginated messages for a conversation.
     *
     * GET /api/chat/conversations/{conversation}/messages
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            return response()->json(['message' => 'You are not part of this conversation.'], 403);
        }

        $messages = $this->creditService
            ->visibleMessagesQuery($conversation, $user)
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 50));

        $messages->getCollection()->transform(
            fn (Message $message) => $this->formatMessageForUser($message, $user)
        );

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Add or update a reaction on a message.
     *
     * POST /api/chat/messages/{message}/reactions
     *
     * If the user already has a reaction on this message, it is
     * overwritten (upsert behaviour). Broadcasts the change to
     * the conversation channel so the other user sees it in real time.
     */
    public function addReaction(StoreMessageReactionRequest $request, Message $message): JsonResponse
    {
        $user = Auth::user();
        $conversation = $message->conversation;

        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            return response()->json(['message' => 'You are not part of this conversation.'], 403);
        }

        $message->addReaction($user->id, $request->input('reaction'));

        broadcast(new MessageReactionUpdated($message->fresh(), $conversation, $user->id))->toOthers();

        return response()->json([
            'data' => [
                'message_id' => $message->id,
                'reactions' => $message->fresh()->reactions,
            ],
        ]);
    }

    /**
     * Remove the authenticated user's reaction from a message.
     *
     * DELETE /api/chat/messages/{message}/reactions
     *
     * Idempotent — returns 204 even if the user had no reaction.
     */
    public function removeReaction(Message $message): JsonResponse
    {
        $user = Auth::user();
        $conversation = $message->conversation;

        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            return response()->json(['message' => 'You are not part of this conversation.'], 403);
        }

        $message->removeReaction($user->id);

        broadcast(new MessageReactionUpdated($message->fresh(), $conversation, $user->id))->toOthers();

        return response()->json([], 204);
    }

    /**
     * Format a message for the API response, masking the body when
     * the message is locked and the current user is the receiver.
     */
    private function formatMessageForUser(Message $message, mixed $user): array
    {
        $isLocked = $message->requires_credit && ! $message->is_paid;
        $isReceiver = $message->receiver_id === $user->id;

        $data = [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'client_id' => $message->client_id,
            'type' => $message->type,
            'is_sent' => $message->is_sent,
            'is_delivered' => $message->is_delivered,
            'is_read' => $message->is_read,
            'is_edited' => $message->is_edited,
            'reply_to_id' => $message->reply_to_id,
            'requires_credit' => $message->requires_credit,
            'credit_cost' => $message->credit_cost,
            'is_paid' => $message->is_paid,
            'payment_verified' => $message->payment_verified,
            'sent_at' => $message->sent_at?->toISOString(),
            'created_at' => $message->created_at->toISOString(),
            'reactions' => $message->reactions,
            'attachments' => $message->isMedia() ? [
                'path' => $message->attachment_path
                    ? Storage::disk(uploads_disk())->url($message->attachment_path)
                    : null,
                'name' => $message->attachment_name,
                'size' => $message->attachment_size,
                'mime' => $message->attachment_mime,
                'meta' => $message->attachment_meta,
            ] : null,
        ];

        // Mask locked content for the receiver — never leak the body or the
        // attachment URL until the member has paid to unlock.
        if ($isLocked && $isReceiver) {
            $data['message'] = '[Locked — pay '.number_format($message->credit_cost, 0).' credits to unlock]';
            $data['is_locked'] = true;
            $data['attachments'] = null;
        } else {
            $data['message'] = $message->message;
            $data['is_locked'] = false;
        }

        return $data;
    }

    /**
     * Handle optional file attachment upload.
     *
     * Stores the file on the public disk under chat/{conversation_id}/,
     * returns structured attachment metadata or null if no file was sent.
     *
     * @return array{path: string, name: string, size: int, mime: string, type: string, meta: array|null}|null
     */
    private function handleAttachmentUpload(SendMessageRequest $request, Conversation $conversation): ?array
    {
        if (! $request->hasFile('attachment')) {
            return null;
        }

        $file = $request->file('attachment');
        $path = $file->store('chat/'.$conversation->id, uploads_disk());

        $mime = $file->getMimeType();
        $type = match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'file',
        };

        $meta = null;
        if (str_starts_with($mime, 'image/')) {
            $imageSize = @getimagesize($file->getRealPath());
            if ($imageSize) {
                $meta = ['width' => $imageSize[0], 'height' => $imageSize[1]];
            }
        }

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $mime,
            'type' => $type,
            'meta' => $meta,
        ];
    }
}

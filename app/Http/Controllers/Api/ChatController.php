<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Chat\ChatCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * API chat controller consumed by the Next.js frontend.
 *
 * sendMessage  — send a message (member pays if requires_credit is set;
 *                escort sends locked, member pays later to unlock)
 * unlockMessage — member pays credits to reveal a locked message's content
 * messages     — load paginated conversation history with locked content masked
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

        // Members pay at send time; escorts never pay — their locked
        // messages are unlocked later by the member receiver.
        $payAtSend = $requiresCredit && $sender->isMember();

        if ($payAtSend && ! $this->creditService->canSendMessage($sender, $conversation)) {
            return response()->json(['message' => 'Insufficient credits.'], 422);
        }

        $creditCost = $request->float('credit_cost', $this->creditService->getMessageCost());

        /** @var Message $message */
        $message = DB::transaction(function () use (
            $conversation, $sender, $receiverId, $request, $requiresCredit, $payAtSend, $creditCost
        ) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'receiver_id' => $receiverId,
                'client_id' => $request->input('client_id'),
                'message' => $request->input('message'),
                'type' => $request->input('type', 'text'),
                'reply_to_id' => $request->input('reply_to_id'),
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

        // Only the receiver of the message can unlock it
        if ($message->receiver_id !== $user->id) {
            return response()->json(['message' => 'Only the message recipient can unlock it.'], 403);
        }

        // Must be a member with a wallet
        if (! $user->isMember() || ! $user->memberProfile) {
            return response()->json(['message' => 'Only members can unlock messages.'], 403);
        }

        // Message must actually require credit and not already be paid
        if (! $message->requires_credit) {
            return response()->json(['message' => 'This message does not require credit to view.'], 422);
        }

        if ($message->is_paid) {
            return response()->json(['message' => 'This message is already unlocked.'], 422);
        }

        // Check the member has enough credits
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
     *
     * Locked messages (requires_credit = true, is_paid = false) have
     * their body replaced with a placeholder for the receiver until
     * payment is verified. Senders always see their own content.
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
     * Format a message for the API response, masking the body when
     * the message is locked and the current user is the receiver.
     */
    private function formatMessageForUser(Message $message, mixed $user): array
    {
        $data = $message->toArray();

        // Mask locked content for the receiver
        $isLocked = $message->requires_credit && ! $message->is_paid;
        $isReceiver = $message->receiver_id === $user->id;

        if ($isLocked && $isReceiver) {
            $data['message'] = '[Locked — pay '.number_format($message->credit_cost, 0).' credits to unlock]';
            $data['is_locked'] = true;
        } else {
            $data['is_locked'] = false;
        }

        return $data;
    }
}

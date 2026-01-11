<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    // Add rate limiting if needed
    // protected $maxAttempts = 5;
    // protected $decayMinutes = 1;

    /**
     * Store a newly created message.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        /** ----------------------------------------------------------------
         * 1️⃣ Validate Request
         * ---------------------------------------------------------------- */
        $validator = Validator::make($request->all(), [
            'conversation_id' => ['required', 'exists:chat_conversations,id'],
            'message' => [
                'required_without:attachment',
                'nullable',
                'string',
                'max:5000'
            ],
            'type' => ['required', 'in:text,image,video,audio,file'],
            'reply_to_id' => ['nullable', 'exists:chat_messages,id'],
            'requires_credit' => ['sometimes', 'boolean'],
            'credit_cost' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        // Dynamic validation based on type
        if ($request->input('type') !== 'text') {
            $validator->sometimes('attachment', 'required', function ($input) {
                return empty($input->message);
            });
        }

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $validator->validated();

        /** ----------------------------------------------------------------
         * 2️⃣ Fetch Conversation with Lock
         * ---------------------------------------------------------------- */
        $conversation = ChatConversation::query()
            ->with(['userOne', 'userTwo'])
            ->lockForUpdate()
            ->findOrFail($data['conversation_id']);

        /** ----------------------------------------------------------------
         * 3️⃣ Authorization & Validation
         * ---------------------------------------------------------------- */
        $this->validateConversationAccess($user, $conversation);

        // Get receiver
        $receiver = $conversation->otherUser($user->id);
        if (!$receiver) {
            throw ValidationException::withMessages([
                'conversation_id' => 'Invalid conversation.',
            ]);
        }

        /** ----------------------------------------------------------------
         * 4️⃣ Block Check
         * ---------------------------------------------------------------- */
        if ($conversation->isBlockedForUser($user->id)) {
            throw ValidationException::withMessages([
                'message' => 'You cannot send messages in this conversation.',
            ]);
        }

        /** ----------------------------------------------------------------
         * 5️⃣ Handle Attachment
         * ---------------------------------------------------------------- */
        $attachmentData = $this->handleAttachment($request);

        // Combine all data for message creation
        $requiresCredit = (bool) ($data['requires_credit'] ?? false);
        $creditCost = (float) ($data['credit_cost'] ?? 0);

        DB::beginTransaction();

        try {
            /** ------------------------------------------------------------
             * 6️⃣ Credit Processing
             * ------------------------------------------------------------ */
            $creditTransaction = null;
            if ($requiresCredit && $creditCost > 0) {
                $creditTransaction = $this->processCreditTransaction(
                    $user,
                    $receiver,
                    $conversation,
                    $creditCost,
                    $data['type'] ?? 'text'
                );
            }

            /** ------------------------------------------------------------
             * 7️⃣ Create Message
             * ------------------------------------------------------------ */
            $message = $this->createMessage(
                $conversation,
                $user,
                $receiver,
                $data,
                $attachmentData,
                $requiresCredit,
                $creditCost,
                $creditTransaction
            );

            /** ------------------------------------------------------------
             * 8️⃣ Update Conversation
             * ------------------------------------------------------------ */
            $conversation->update([
                'last_message_at' => now(),
                'last_message_id' => $message->id,
            ]);

            DB::commit();

            /** ----------------------------------------------------------------
             * 9️⃣ Broadcast Message (AFTER COMMIT)
             * ---------------------------------------------------------------- */
            broadcast(new MessageSent($message))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            // Clean up uploaded file if transaction fails
            if (isset($attachmentData['attachment_path'])) {
                Storage::disk('public')->delete($attachmentData['attachment_path']);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Validate conversation access.
     */
    private function validateConversationAccess($user, $conversation)
    {
        if (!in_array($user->id, [$conversation->user_one_id, $conversation->user_two_id])) {
            throw ValidationException::withMessages([
                'conversation_id' => 'You are not part of this conversation.',
            ]);
        }

        // Check if conversation is active
        if ($conversation->status !== 'active') {
            throw ValidationException::withMessages([
                'conversation_id' => 'This conversation is not active.',
            ]);
        }
    }

    /**
     * Process credit transaction.
     */
    private function processCreditTransaction($sender, $receiver, $conversation, $creditCost, $messageType)
    {
        // Validate credit rules
        $this->validateCreditMessage($sender, $receiver, $conversation, $creditCost);

        // Use database decrement with condition to prevent negative credits
        if ($sender->credits < $creditCost) {
            throw ValidationException::withMessages([
                'credits' => 'Insufficient credits to send this message.',
            ]);
        }

        // Deduct from sender
        $sender->decrement('credits', $creditCost);
        $sender->increment('total_credits_spent', $creditCost);

        // Create credit transaction record
        $creditTransaction = CreditTransaction::create([
            'user_id' => $sender->id,
            'recipient_id' => $receiver->id,
            'type' => 'usage',
            'amount' => $creditCost,
            'balance_before' => $sender->credits,
            'balance_after' => $sender->fresh()->credits,
            'status' => 'completed',
            'meta' => [
                'conversation_id' => $conversation->id,
                'message_type' => $messageType,
                'receiver_role' => $receiver->getRoleNames()->first(),
            ],
        ]);

        // Update conversation stats
        $conversation->increment('total_credits_spent', $creditCost);
        if ($receiver->hasRole('escort')) {
            $conversation->increment('total_earnings', $creditCost);
        }
        $conversation->update([
            'credit_payer_id' => $sender->id,
            'is_paid_conversation' => true,
        ]);

        return $creditTransaction;
    }

    /**
     * Create message record.
     */
    private function createMessage($conversation, $sender, $receiver, $data, $attachmentData, $requiresCredit, $creditCost, $creditTransaction = null)
    {
        $messageData = [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message' => $data['message'] ?? null,
            'type' => $data['type'] ?? 'text',
            'reply_to_id' => $data['reply_to_id'] ?? null,
            'is_read' => false,
            'is_delivered' => true,
            'delivered_at' => now(),
            'requires_credit' => $requiresCredit,
            'credit_cost' => $creditCost,
            'is_paid' => $requiresCredit && $creditCost > 0,
            'payment_verified' => $requiresCredit && $creditCost > 0,
        ];

        // Add attachment data if present
        if ($attachmentData) {
            $messageData = array_merge($messageData, $attachmentData);
        }

        // Add credit transaction reference
        if ($creditTransaction) {
            $messageData['credit_transaction_id'] = $creditTransaction->id;
        }

        // Add message hash for duplicate detection
        $messageData['message_hash'] = md5(
            $conversation->id .
            $sender->id .
            ($data['message'] ?? '') .
            ($attachmentData['attachment_hash'] ?? '') .
            now()->timestamp
        );

        return ChatMessage::create($messageData);
    }

    /**
     * Validate credit message rules.
     */
    private function validateCreditMessage($sender, $receiver, $conversation, $creditCost)
    {
        // Credit cost must be greater than zero
        if ($creditCost <= 0) {
            throw ValidationException::withMessages([
                'credit_cost' => 'Credit cost must be greater than zero.',
            ]);
        }

        // Credit cost cannot exceed 1000
        if ($creditCost > 1000) {
            throw ValidationException::withMessages([
                'credit_cost' => 'Credit cost cannot exceed 1000.',
            ]);
        }

        // Only members can send paid messages to escorts
        if (!$sender->hasRole('member') || !$receiver->hasRole('escort')) {
            throw ValidationException::withMessages([
                'requires_credit' => 'Paid messages are only allowed from members to escorts.',
            ]);
        }
    }

    /**
     * Handle file attachment upload.
     */
    private function handleAttachment(Request $request): ?array
    {
        if (!$request->hasFile('attachment')) {
            return null;
        }

        $file = $request->file('attachment');

        // Validate file type based on message type
        $allowedTypes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'avi', 'mov', 'wmv'],
            'audio' => ['mp3', 'wav', 'ogg'],
            'file' => ['pdf', 'doc', 'docx', 'txt', 'zip'],
        ];

        $type = $request->input('type');
        if (isset($allowedTypes[$type])) {
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedTypes[$type])) {
                throw ValidationException::withMessages([
                    'attachment' => "Invalid file type for $type message. Allowed: " . implode(', ', $allowedTypes[$type]),
                ]);
            }
        }

        // Generate secure filename
        $filename = hash('sha256', $file->getClientOriginalName() . time()) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('chat/attachments/' . date('Y/m'), $filename, 'public');

        return [
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_size' => $file->getSize(),
            'attachment_mime' => $file->getMimeType(),
            'attachment_hash' => hash_file('sha256', $file->getRealPath()),
            'attachment_meta' => [
                'extension' => $file->getClientOriginalExtension(),
                'original_name' => $file->getClientOriginalName(),
            ],
        ];
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:chat_conversations,id',
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:chat_messages,id',
        ]);

        $user = Auth::user();

        ChatMessage::whereIn('id', $request->message_ids)
            ->where('conversation_id', $request->conversation_id)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read',
        ]);
    }
}
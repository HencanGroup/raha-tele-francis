<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    /**
     * Store a newly created message.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        /* -------------------------------------------------------------
         | 1️⃣ Validate Request
         |-------------------------------------------------------------*/
        $validator = Validator::make($request->all(), [
            'conversation_id' => ['required', 'exists:conversations,id'],
            'client_id' => ['required', 'uuid'],
            'message' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', 'in:text,image,video,audio,file,sticker,gif'],
            'reply_to_id' => ['nullable', 'exists:chat_messages,id'],
            'requires_credit' => ['sometimes', 'boolean'],
            'credit_cost' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($request->type !== 'text') {
            $validator->after(function ($validator) use ($request) {
                if (!$request->hasFile('attachment') && empty($request->message)) {
                    $validator->errors()->add(
                        'attachment',
                        'Attachment is required for non-text messages.'
                    );
                }
            });
        }

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $validator->validated();

        /* -------------------------------------------------------------
         | 2️⃣ Fetch Conversation (LOCKED)
         |-------------------------------------------------------------*/
        $conversation = Conversation::query()
            ->lockForUpdate()
            ->findOrFail($data['conversation_id']);

        $this->validateConversationAccess($user, $conversation);

        $receiver = $conversation->otherUser($user->id);
        if (!$receiver) {
            throw ValidationException::withMessages([
                'conversation_id' => 'Invalid conversation.',
            ]);
        }

        if ($conversation->isBlockedForUser($user->id)) {
            throw ValidationException::withMessages([
                'message' => 'You cannot send messages in this conversation.',
            ]);
        }

        /* -------------------------------------------------------------
         | 3️⃣ Handle Attachment
         |-------------------------------------------------------------*/
        $attachmentData = $this->handleAttachment($request);

        $requiresCredit = (bool) ($data['requires_credit'] ?? false);
        $creditCost = (float) ($data['credit_cost'] ?? 0);

        DB::beginTransaction();

        try {
            /* ---------------------------------------------------------
             | 4️⃣ Credit Processing
             |---------------------------------------------------------*/
            $creditTransaction = null;

            if ($requiresCredit && $creditCost > 0) {
                $creditTransaction = $this->processCreditTransaction(
                    $user,
                    $receiver,
                    $conversation,
                    $creditCost,
                    $data['type']
                );
            }

            /* ---------------------------------------------------------
             | 5️⃣ Create Message
             |---------------------------------------------------------*/
            $message = Message::create(array_merge([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'receiver_id' => $receiver->id,
                'client_id' => $data['client_id'],

                'message' => $data['message'] ?? null,
                'type' => $data['type'],
                'reply_to_id' => $data['reply_to_id'] ?? null,

                'is_sent' => true,
                'sent_at' => now(),

                'is_read' => false,

                'requires_credit' => $requiresCredit,
                'credit_cost' => $creditCost,
                'is_paid' => $requiresCredit && $creditCost > 0,
                'payment_verified' => $requiresCredit && $creditCost > 0,

                'credit_transaction_id' => $creditTransaction?->id,
            ], $attachmentData ?? []));

            /* ---------------------------------------------------------
             | 6️⃣ Update Conversation
             |---------------------------------------------------------*/
            $conversation->update([
                'last_message_at' => now(),
                'last_message_id' => $message->id,
            ]);

            DB::commit();

            /* ---------------------------------------------------------
             | 7️⃣ Broadcast (AFTER COMMIT)
             |---------------------------------------------------------*/
            broadcast(new MessageSent($message))->toOthers();

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            if (!empty($attachmentData['attachment_path'])) {
                Storage::disk('public')->delete($attachmentData['attachment_path']);
            }

            throw $e;
        }
    }

    /* =============================================================
     | Helpers
     |=============================================================*/

    private function validateConversationAccess($user, $conversation)
    {
        if (
            !in_array($user->id, [
                $conversation->user_one_id,
                $conversation->user_two_id,
            ])
        ) {
            throw ValidationException::withMessages([
                'conversation_id' => 'You are not part of this conversation.',
            ]);
        }

        if ($conversation->status !== 'active') {
            throw ValidationException::withMessages([
                'conversation_id' => 'Conversation is not active.',
            ]);
        }
    }

    private function processCreditTransaction($sender, $receiver, $conversation, $creditCost, $type)
    {
        if ($sender->credits < $creditCost) {
            throw ValidationException::withMessages([
                'credits' => 'Insufficient credits.',
            ]);
        }

        $sender->deductCredits($creditCost);

        return CreditTransaction::create([
            'user_id' => $sender->id,
            'recipient_id' => $receiver->id,
            'type' => 'usage',
            'amount' => $creditCost,
            'balance_before' => $sender->credits,
            'balance_after' => $sender->credits - $creditCost,
            'status' => 'completed',
            'meta' => [
                'conversation_id' => $conversation->id,
                'message_type' => $type,
            ],
        ]);
    }

    private function handleAttachment(Request $request): ?array
    {
        if (!$request->hasFile('attachment')) {
            return null;
        }

        $file = $request->file('attachment');

        $filename = uniqid('chat_', true) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs(
            'chat/attachments/' . date('Y/m'),
            $filename,
            'public'
        );

        return [
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_size' => $file->getSize(),
            'attachment_mime' => $file->getMimeType(),
            'attachment_meta' => [
                'extension' => $file->getClientOriginalExtension(),
            ],
        ];
    }
}

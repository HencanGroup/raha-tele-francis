<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

// Queued via ShouldQueue so a slow/dead Reverb server never fails the chat request —
// the broadcast runs on the queue worker instead (see composer dev script).
class NewMessage implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public Conversation $conversation,
    ) {
        $this->message->load(['sender', 'receiver']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->conversation->id),
            new PrivateChannel('user.'.$this->message->receiver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.message';
    }

    public function broadcastWith(): array
    {
        $data = [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'message' => $this->message->message,
            'type' => $this->message->type,
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'display_name' => $this->message->sender->display_name,
                'profile_photo_url' => $this->message->sender->profile_photo_url,
            ],
            'sender_id' => $this->message->sender_id,
            'receiver' => [
                'id' => $this->message->receiver->id,
                'name' => $this->message->receiver->name,
            ],
            'receiver_id' => $this->message->receiver_id,
            'created_at' => $this->message->created_at->toISOString(),
            'is_sent' => $this->message->is_sent,
            'is_delivered' => $this->message->is_delivered,
            'is_read' => $this->message->is_read,
            'client_id' => $this->message->client_id,
            'requires_credit' => $this->message->requires_credit,
            'credit_cost' => $this->message->credit_cost,
            'is_paid' => $this->message->is_paid,
            'payment_verified' => $this->message->payment_verified,
        ];

        if ($this->message->isMedia()) {
            $data['attachments'] = [
                // Send the absolute public URL (not the raw storage path) so the
                // recipient's <img>/<video> src and download href resolve —
                // mirrors Api\ChatController::formatMessageForUser.
                'path' => $this->message->attachment_path
                    ? Storage::disk(uploads_disk())->url($this->message->attachment_path)
                    : null,
                'name' => $this->message->attachment_name,
                'size' => $this->message->attachment_size,
                'mime' => $this->message->attachment_mime,
                'meta' => $this->message->attachment_meta,
            ];
        }

        return $data;
    }
}

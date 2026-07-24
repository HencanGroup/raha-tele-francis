<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
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
            'receiver' => [
                'id' => $this->message->receiver->id,
                'name' => $this->message->receiver->name,
            ],
            'created_at' => $this->message->created_at->toISOString(),
            'is_read' => $this->message->is_read,
            'client_id' => $this->message->client_id,
        ];

        if ($this->message->isMedia()) {
            $data['attachments'] = [
                'path' => $this->message->attachment_path,
                'name' => $this->message->attachment_name,
                'size' => $this->message->attachment_size,
                'mime' => $this->message->attachment_mime,
                'meta' => $this->message->attachment_meta,
            ];
        }

        return $data;
    }
}

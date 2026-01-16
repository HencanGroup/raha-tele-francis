<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): PrivateChannel
    {
        // Notify ONLY the sender
        return new PrivateChannel('user.' . $this->message->sender_id);
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'read_at' => $this->message->read_at,
        ];
    }
}

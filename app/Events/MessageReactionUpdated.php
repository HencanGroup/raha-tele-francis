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

// Queued via ShouldQueue so reaction updates don't block the API request.
class MessageReactionUpdated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public Conversation $conversation,
        public int $userId,
    ) {
        $this->message->load(['sender', 'receiver']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.reaction';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'user_id' => $this->userId,
            'reactions' => $this->message->reactions,
        ];
    }
}

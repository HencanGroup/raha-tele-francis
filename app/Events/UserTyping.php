<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $conversationId;
    public $isTyping;

    public function __construct($userId, $conversationId, $isTyping)
    {
        $this->userId = $userId;
        $this->conversationId = $conversationId;
        $this->isTyping = $isTyping;

        // Log the event trigger
        Log::info('UserTyping event triggered', [
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'is_typing' => $isTyping,
            'time' => now()->toISOString()
        ]);

        // Prevent broadcasting to the typing user
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn()
    {
        // Broadcast to both users in the conversation
        return [
            new PrivateChannel('user.' . $this->userId), // For the receiver
        ];
    }

    public function broadcastAs()
    {
        return 'typing';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'conversation_id' => $this->conversationId,
            'is_typing' => $this->isTyping,
            'timestamp' => now()->toISOString(),
        ];
    }
}
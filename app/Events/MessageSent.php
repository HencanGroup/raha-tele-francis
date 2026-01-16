<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversation;
    public $sender;

    public function __construct($message, $conversation, $sender)
    {
        $this->message = $message;
        $this->conversation = $conversation;
        $this->sender = $sender;

        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): array
    {
        // Send to both users in the conversation
        return [
            new PrivateChannel('App.Models.User.' . $this->message->receiver_id),
            new PrivateChannel('App.Models.User.' . $this->message->sender_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'conversation' => $this->conversation,
            'sender' => $this->sender,
        ];
    }
}
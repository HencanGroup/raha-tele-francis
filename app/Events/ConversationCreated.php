<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Queued via ShouldQueue so the new-conversation event never blocks chat.start.
class ConversationCreated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    public $initiatorId;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation, int $initiatorId)
    {
        $this->conversation = $conversation->load(['userOne', 'userTwo']);
        $this->initiatorId = $initiatorId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->conversation->user_one_id),
            new PrivateChannel('user.'.$this->conversation->user_two_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new.conversation';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $otherUser = $this->conversation->user_one_id === $this->initiatorId
            ? $this->conversation->userTwo
            : $this->conversation->userOne;

        return [
            'id' => $this->conversation->id,
            'user_one_id' => $this->conversation->user_one_id,
            'user_two_id' => $this->conversation->user_two_id,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'profile_photo_url' => $otherUser->profile_photo_url,
            ],
            'last_message_at' => $this->conversation->last_message_at?->toISOString(),
            'created_at' => $this->conversation->created_at->toISOString(),
            'unread_count' => 0,
        ];
    }
}

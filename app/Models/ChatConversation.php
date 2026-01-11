<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'status',
        'last_message_at',

        'user_one_muted',
        'user_two_muted',
        'user_one_archived',
        'user_two_archived',
        'user_one_blocked',
        'user_two_blocked',

        'user_one_last_read_at',
        'user_two_last_read_at',

        'is_paid_conversation',
        'total_credits_spent',
        'total_earnings',
        'credit_payer_id',
    ];

    protected $casts = [
        'user_one_muted' => 'boolean',
        'user_two_muted' => 'boolean',
        'user_one_archived' => 'boolean',
        'user_two_archived' => 'boolean',
        'user_one_blocked' => 'boolean',
        'user_two_blocked' => 'boolean',
        'is_paid_conversation' => 'boolean',

        'last_message_at' => 'datetime',
        'user_one_last_read_at' => 'datetime',
        'user_two_last_read_at' => 'datetime',

        'total_credits_spent' => 'decimal:2',
        'total_earnings' => 'decimal:2',
    ];

    /* -----------------------------------------------------------------
     | Relationships
     |-----------------------------------------------------------------*/

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function creditPayer()
    {
        return $this->belongsTo(User::class, 'credit_payer_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')
            ->latestOfMany();
    }

    /* -----------------------------------------------------------------
     | Query Helpers
     |-----------------------------------------------------------------*/

    /**
     * Get conversation between two users (bidirectional).
     */
    public static function between(int $userA, int $userB)
    {
        return self::where(function ($query) use ($userA, $userB) {
            $query->where('user_one_id', $userA)
                ->where('user_two_id', $userB);
        })->orWhere(function ($query) use ($userA, $userB) {
            $query->where('user_one_id', $userB)
                ->where('user_two_id', $userA);
        });
    }

    /* -----------------------------------------------------------------
     | Message Helpers
     |-----------------------------------------------------------------*/

    public function unreadMessagesForUser(int $userId)
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->where('is_deleted', false);
    }

    /* -----------------------------------------------------------------
     | User State Helpers
     |-----------------------------------------------------------------*/

    public function otherUser(int $userId)
    {
        return $this->user_one_id === $userId
            ? $this->userTwo
            : $this->userOne;
    }

    public function isArchivedForUser(int $userId): bool
    {
        return $this->user_one_id === $userId
            ? (bool) $this->user_one_archived
            : (bool) $this->user_two_archived;
    }

    public function isMutedForUser(int $userId): bool
    {
        return $this->user_one_id === $userId
            ? (bool) $this->user_one_muted
            : (bool) $this->user_two_muted;
    }

    public function isBlockedForUser(int $userId): bool
    {
        return $this->user_one_id === $userId
            ? (bool) $this->user_two_blocked
            : (bool) $this->user_one_blocked;
    }
}

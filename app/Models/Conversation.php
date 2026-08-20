<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
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
        return $this->hasMany(Message::class, 'conversation_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class, 'conversation_id')
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
            ->where(function ($query) use ($userId) {
                // Check if message is not deleted for this user
                if ($this->user_one_id === $userId) {
                    $query->where('user_one_deleted', false);
                } else {
                    $query->where('user_two_deleted', false);
                }
            });
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

    /**
     * Check if message is deleted for a specific user
     */
    public function isMessageDeletedForUser(Message $message, int $userId): bool
    {
        return $this->user_one_id === $userId
            ? (bool) $message->user_one_deleted
            : (bool) $message->user_two_deleted;
    }

    /**
     * Get all visible messages for a user
     */
    public function visibleMessagesForUser(int $userId)
    {
        // Both the sender and receiver branches live inside ONE outer group so
        // the conversation_id constraint (added by messages()) applies to both.
        // A top-level orWhere for the receiver branch would drop that filter
        // (SQL evaluates AND before OR) and leak messages from other chats.
        $deletedColumn = $this->user_one_id === $userId
            ? 'user_one_deleted'
            : 'user_two_deleted';

        return $this->messages()
            ->where(function ($query) use ($userId, $deletedColumn) {
                $query->where('sender_id', $userId)
                    ->where($deletedColumn, false)
                    ->orWhere('receiver_id', $userId)
                    ->where($deletedColumn, false);
            });
    }
}

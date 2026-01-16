<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /* -----------------------------------------------------------------
     | Mass Assignment
     |-----------------------------------------------------------------*/

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'receiver_id',
        'client_id',

        'message',
        'type',

        'attachment_path',
        'attachment_name',
        'attachment_size',
        'attachment_mime',
        'attachment_meta',

        'metadata',
        'reply_to_id',

        'is_sent',
        'sent_at',

        'is_delivered',
        'delivered_at',

        'is_read',
        'read_at',

        'is_edited',
        'edited_at',

        'user_one_deleted',
        'user_one_deleted_at',

        'user_two_deleted',
        'user_two_deleted_at',

        'reactions',

        'requires_credit',
        'credit_cost',
        'credit_transaction_id',
        'is_paid',
        'payment_verified',
    ];

    /* -----------------------------------------------------------------
     | Attribute Casting
     |-----------------------------------------------------------------*/

    protected $casts = [
        'client_id' => 'string',

        'is_sent' => 'boolean',
        'sent_at' => 'datetime',

        'is_delivered' => 'boolean',
        'delivered_at' => 'datetime',

        'is_read' => 'boolean',
        'read_at' => 'datetime',

        'is_edited' => 'boolean',
        'edited_at' => 'datetime',

        'user_one_deleted' => 'boolean',
        'user_one_deleted_at' => 'datetime',

        'user_two_deleted' => 'boolean',
        'user_two_deleted_at' => 'datetime',

        'attachment_meta' => 'array',
        'metadata' => 'array',
        'reactions' => 'array',

        'requires_credit' => 'boolean',
        'credit_cost' => 'decimal:2',
        'is_paid' => 'boolean',
        'payment_verified' => 'boolean',
    ];

    /* -----------------------------------------------------------------
     | Relationships
     |-----------------------------------------------------------------*/

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function creditTransaction()
    {
        return $this->belongsTo(CreditTransaction::class);
    }

    /* -----------------------------------------------------------------
     | Scopes
     |-----------------------------------------------------------------*/

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeDelivered($query)
    {
        return $query->where('is_delivered', true);
    }

    public function scopeVisibleForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->where('user_one_deleted', false);
            })->orWhere(function ($q) use ($userId) {
                $q->where('receiver_id', $userId)
                    ->where('user_two_deleted', false);
            });
        });
    }

    /* -----------------------------------------------------------------
     | Helpers
     |-----------------------------------------------------------------*/

    public function isMedia(): bool
    {
        return in_array($this->type, [
            'image',
            'video',
            'audio',
            'file',
            'gif',
            'sticker',
        ]);
    }

    public function addReaction(int $userId, string $reaction): void
    {
        $reactions = $this->reactions ?? [];
        $reactions[$userId] = $reaction;

        $this->update(['reactions' => $reactions]);
    }

    public function removeReaction(int $userId): void
    {
        $reactions = $this->reactions ?? [];
        unset($reactions[$userId]);

        $this->update(['reactions' => $reactions]);
    }
}

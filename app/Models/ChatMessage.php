<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'receiver_id',
        'message',
        'type',
        'attachment_path',
        'attachment_name',
        'attachment_size',
        'attachment_mime',
        'attachment_meta',
        'metadata',
        'reply_to_id',
        'is_read',
        'read_at',
        'is_delivered',
        'delivered_at',
        'is_edited',
        'edited_at',
        'is_deleted',
        'deleted_at',
        'reactions',
        'requires_credit',
        'credit_cost',
        'credit_transaction_id',
        'is_paid',
        'payment_verified',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_delivered' => 'boolean',
        'delivered_at' => 'datetime',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
        'attachment_meta' => 'array',
        'metadata' => 'array',
        'reactions' => 'array',
        'requires_credit' => 'boolean',
        'credit_cost' => 'decimal:2',
        'is_paid' => 'boolean',
        'payment_verified' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_id');
    }

    public function creditTransaction()
    {
        return $this->belongsTo(CreditTransaction::class);
    }

    public function replies()
    {
        return $this->hasMany(ChatMessage::class, 'reply_to_id');
    }

    // Scope for unread messages
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // Scope for not deleted messages
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    // Check if message is media
    public function isMedia()
    {
        return in_array($this->type, ['image', 'video', 'audio', 'file']);
    }

    // Add reaction to message
    public function addReaction($userId, $reaction)
    {
        $reactions = $this->reactions ?? [];
        $reactions[$userId] = $reaction;
        $this->reactions = $reactions;
        $this->save();
    }

    // Remove reaction from message
    public function removeReaction($userId)
    {
        $reactions = $this->reactions ?? [];
        unset($reactions[$userId]);
        $this->reactions = $reactions;
        $this->save();
    }
}
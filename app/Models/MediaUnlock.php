<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records that a member has paid to unlock a private escort media item.
 *
 * Each row is idempotent via the (user_id, escort_resource_id) unique
 * constraint — a member can only unlock a given photo/video once.
 */
class MediaUnlock extends Model
{
    protected $fillable = [
        'user_id',
        'escort_resource_id',
        'credits_spent',
    ];

    protected $casts = [
        'credits_spent' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(EscortResource::class, 'escort_resource_id');
    }
}

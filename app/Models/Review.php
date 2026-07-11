<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'escort_id',
        'rating',
        'comment',
        'is_verified',
        'is_visible',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function escort()
    {
        return $this->belongsTo(Escort::class);
    }

    // Scope for visible reviews
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    // Scope for verified reviews
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // Scope for recent reviews
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

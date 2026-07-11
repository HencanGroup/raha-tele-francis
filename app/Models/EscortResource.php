<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EscortResource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'escort_id',
        'type',
        'path',
        'thumbnail_path',
        'caption',
        'is_primary',
        'is_verified',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function escort()
    {
        return $this->belongsTo(Escort::class);
    }

    // Scope for photos
    public function scopePhotos($query)
    {
        return $query->where('type', 'photo');
    }

    // Scope for videos
    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    // Scope for verified resources
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // Scope for public resources
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}

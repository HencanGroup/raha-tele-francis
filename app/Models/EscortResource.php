<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Append computed URL accessors so they appear in toArray() / Inertia props.
     */
    protected $appends = ['url', 'thumbnail_url'];

    /**
     * Full public URL for the media file on the uploads disk.
     */
    public function getUrlAttribute(): ?string
    {
        return $this->path ? Storage::disk(uploads_disk())->url($this->path) : null;
    }

    /**
     * Full public URL for the thumbnail (falls back to the main url).
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path
            ? Storage::disk(uploads_disk())->url($this->thumbnail_path)
            : $this->url;
    }

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

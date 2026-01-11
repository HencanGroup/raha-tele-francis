<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Escort extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'stage_name',
        'gender',
        'birth_date',
        'age',
        'bio',
        'profile_picture',
        'county_id',
        'town_id',
        'location',
        'latitude',
        'longitude',
        'available',
        'working_hours',
        'height',
        'weight',
        'body_type',
        'hair_color',
        'eye_color',
        'services',
        'special_features',
        'languages',
        'rate_per_hour',
        'rate_per_night',
        'custom_rates',
        'is_verified',
        'verification_documents',
        'verification_status',
        'view_count',
        'rating',
        'review_count',
        'total_bookings',
        'earnings',
        'balance',
        'featured',
        'accepting_new_clients',
        'incall_available',
        'outcall_available',
        'travel_options',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'available' => 'boolean',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'services' => 'array',
        'special_features' => 'array',
        'languages' => 'array',
        'rate_per_hour' => 'decimal:2',
        'rate_per_night' => 'decimal:2',
        'custom_rates' => 'array',
        'is_verified' => 'boolean',
        'verification_documents' => 'array',
        'rating' => 'decimal:2',
        'earnings' => 'decimal:2',
        'balance' => 'decimal:2',
        'featured' => 'boolean',
        'accepting_new_clients' => 'boolean',
        'incall_available' => 'boolean',
        'outcall_available' => 'boolean',
        'travel_options' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function town()
    {
        return $this->belongsTo(Town::class);
    }

    public function resources()
    {
        return $this->hasMany(EscortResource::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function conversations()
    {
        return $this->user->conversations();
    }

    public function primaryPhoto()
    {
        return $this->hasOne(EscortResource::class)->where('is_primary', true);
    }

    public function verifiedPhotos()
    {
        return $this->resources()->where('type', 'photo')->where('is_verified', true);
    }

    public function publicResources()
    {
        return $this->resources()->where('is_public', true);
    }

    // Calculate age automatically if not provided
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->birth_date && !$model->age) {
                $model->age = now()->diffInYears($model->birth_date);
            }
        });
    }

    // Scope for available escorts
    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    // Scope for verified escorts
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // Scope for featured escorts
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
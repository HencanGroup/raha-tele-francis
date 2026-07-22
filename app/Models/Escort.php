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
        'bio',
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

    /* -----------------------------------------------------------------
     | Relationships
     |-----------------------------------------------------------------*/

    public function user()
    {
        return $this->belongsTo(User::class);
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

    /**
     * Credit transactions for this escort's user account.
     *
     * Links through the shared user_id column — both escorts and
     * credit_transactions reference the same users.id.
     */
    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class, 'user_id', 'user_id');
    }

    public function primaryPhoto()
    {
        return $this->hasOne(EscortResource::class)->where(
            'is_primary',
            true
        );
    }

    public function verifiedPhotos()
    {
        return $this->resources()->where(
            'type',
            'photo'
        )->where('is_verified', true);
    }

    public function publicResources()
    {
        return $this->resources()->where('is_public', true);
    }

    /* -----------------------------------------------------------------
     | Scopes
     |-----------------------------------------------------------------*/

    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeSearchable($query)
    {
        return $query->available()
            ->verified()
            ->where('accepting_new_clients', true)
            ->withUserInfo();
    }

    /* -----------------------------------------------------------------
     | Helpers
     |-----------------------------------------------------------------*/

    /**
     * Calculate and update escort's rating
     */
    public function updateRating(): void
    {
        $this->rating = $this->reviews()->avg('rating') ?? 0;
        $this->review_count = $this->reviews()->count();
        $this->save();
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * Check if escort accepts specific service type
     */
    public function offersService(string $service): bool
    {
        return in_array($service, $this->services ?? []);
    }

    /**
     * Get available service types
     */
    public function getAvailableServices(): array
    {
        return $this->services ?? [];
    }

    /**
     * Check if escort accepts incall/outcall
     */
    public function acceptsIncall(): bool
    {
        return $this->incall_available;
    }

    public function acceptsOutcall(): bool
    {
        return $this->outcall_available;
    }

    /**
     * Check if escort has available travel options
     */
    public function hasTravelOptions(): bool
    {
        return ! empty($this->travel_options);
    }

    /**
     * Get verification status text
     */
    public function getVerificationStatusText(): string
    {
        return match ($this->verification_status) {
            'pending' => 'Pending Verification',
            'verified' => 'Verified',
            'rejected' => 'Verification Rejected',
            default => 'Not Submitted',
        };
    }
}

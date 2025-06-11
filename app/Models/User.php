<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $gender
 * @property string|null $searching_for
 * @property string|null $birth_date
 * @property int|null $age
 * @property string|null $bio
 * @property string|null $profile_picture
 * @property array|null $gallery
 * @property string|null $location
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $phone_number
 * @property bool $phone_verified
 * @property string|null $stripe_customer_id
 * @property bool $is_verified
 * @property string|null $verification_documents
 * @property int $view_count
 * @property float|null $rating
 * @property int $review_count
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<Subscription> $subscriptions
 * @property-read Subscription|null $activeSubscription
 * @property-read \Illuminate\Database\Eloquent\Collection<Review> $reviews
 * @property-read \Illuminate\Database\Eloquent\Collection<Review> $givenReviews
 * @property-read \Illuminate\Database\Eloquent\Collection<UserVerification> $verifications
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_BANNED = 'banned';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'gender',
        'searching_for',
        'birth_date',
        'bio',
        'profile_picture',
        'gallery',
        'location',
        'latitude',
        'longitude',
        'phone_number',
        'verification_documents',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'stripe_customer_id',
        'verification_documents',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified' => 'boolean',
            'is_escort' => 'boolean',
            'is_verified' => 'boolean',
            'view_count' => 'integer',
            'rating' => 'float',
            'review_count' => 'integer',
            'gallery' => 'array',
            'birth_date' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
     */
    protected $dates = [
        'email_verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get all subscriptions for the user.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->orderBy('created_at', 'desc');
    }

    public function mpesaPayments()
    {
        return $this->hasMany(MpesaPayment::class);
    }

    /**
     * Get the user's active subscription.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->active()->latest();
    }

    /**
     * Get all reviews received by the user.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /**
     * Get all reviews given by the user.
     */
    public function givenReviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /**
     * Get all verification attempts for the user.
     */
    public function verifications()
    {
        return $this->hasMany(UserVerification::class);
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the user is banned.
     */
    public function isBanned(): bool
    {
        return $this->status === self::STATUS_BANNED;
    }

    /**
     * Check if the user has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription !== null;
    }

    /**
     * Get the current plan through the active subscription.
     */
    public function plan()
    {
        return $this->hasOneThrough(
            Plan::class,
            Subscription::class,
            'user_id', // Foreign key on subscriptions table
            'id', // Foreign key on plans table
            'id', // Local key on users table
            'plan_id' // Local key on subscriptions table
        )->whereHas('subscription', function ($query) {
            $query->active();
        });
    }

    /**
     * Check if the user is fully verified (email and phone).
     */
    public function isFullyVerified(): bool
    {
        return $this->hasVerifiedEmail()
            && $this->phone_verified
            && $this->is_verified;
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include users with active subscriptions.
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->whereHas('activeSubscription');
    }

    /**
     * Scope a query to only include verified users.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true)
            ->whereNotNull('email_verified_at')
            ->where('phone_verified', true);
    }

    /**
     * The channels the user receives notification broadcasts on.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.' . $this->id;
    }

    /**
     * Get the user's age based on birth date.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : null;
    }

    /**
     * Determine if the user has a specific feature based on their active subscription's plan.
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->hasActiveSubscription()) {
            return false;
        }

        return $this->activeSubscription->plan->features()
            ->where('name', $feature)
            ->where('value', '!=', 'false')
            ->exists();
    }
}

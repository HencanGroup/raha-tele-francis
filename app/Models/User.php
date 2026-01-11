<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'phone_verified',
        'credits',
        'total_credits_earned',
        'total_credits_spent',
        'last_credit_purchase_at',
        'credits_expire_at',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified' => 'boolean',
        'credits' => 'decimal:2',
        'total_credits_earned' => 'decimal:2',
        'total_credits_spent' => 'decimal:2',
        'last_credit_purchase_at' => 'datetime',
        'credits_expire_at' => 'datetime',
    ];

    protected $appends = [
        'profile',              // ✅ unified profile
        'profile_type',
        'display_name',
        'profile_photo_url',
        'role_name',
    ];

    /* -----------------------------------------------------------------
     | Relationships
     |-----------------------------------------------------------------*/

    // 🔹 Rename MEMBER profile relationship
    public function memberProfile()
    {
        return $this->hasOne(UserProfile::class);
    }

    // 🔹 Escort profile
    public function escortProfile()
    {
        return $this->hasOne(Escort::class);
    }

    /* -----------------------------------------------------------------
     | Unified Profile Accessor (🔥 key part)
     |-----------------------------------------------------------------*/

    public function getProfileAttribute()
    {
        return $this->hasRole('escort')
            ? $this->escortProfile
            : $this->memberProfile;
    }

    public function getProfileTypeAttribute(): string
    {
        return $this->hasRole('escort') ? 'escort' : 'member';
    }

    public function hasProfile(): bool
    {
        return (bool) $this->profile;
    }

    public function getProfilePhotoUrlAttribute()
    {
        if (!$this->profile) {
            return null;
        }

        return $this->hasRole('escort')
            ? $this->profile->profile_picture
            : $this->profile->avatar;
    }

    public function getDisplayNameAttribute(): string
    {
        if (!$this->profile) {
            return $this->name;
        }

        return $this->hasRole('escort')
            ? ($this->profile->display_name ?? $this->name)
            : ($this->profile->full_name ?? $this->name);
    }

    /* -----------------------------------------------------------------
     | Conversations
     |-----------------------------------------------------------------*/

    public function conversations()
    {
        return ChatConversation::where('user_one_id', $this->id)
            ->orWhere('user_two_id', $this->id);
    }

    /* -----------------------------------------------------------------
     | Credit transactions
     |-----------------------------------------------------------------*/

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /* -----------------------------------------------------------------
     | Roles
     |-----------------------------------------------------------------*/

    public function hasUserRole(string $role): bool
    {
        return $this->hasRole($role);
    }

    public function getRoleNameAttribute(): string
    {
        return $this->getRoleNames()->first() ?? 'member';
    }

    /* -----------------------------------------------------------------
     | Subscription
     |-----------------------------------------------------------------*/

    public function hasActiveSubscription(): bool
    {
        return $this->credits_expire_at?->isFuture() ?? false;
    }
}

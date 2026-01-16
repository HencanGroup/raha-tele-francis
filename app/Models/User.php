<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'phone_verified',
        'gender',
        'date_of_birth',
        'age',
        'profile_picture',
        'county_id',
        'town_id',
        'location',
        'latitude',
        'longitude',
        'meta_data',
        'credits',
        'total_credits_earned',
        'total_credits_spent',
        'last_credit_purchase_at',
        'credits_expire_at',
        'status',
        'last_seen',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified' => 'boolean',
        'date_of_birth' => 'date',
        'meta_data' => 'array',
        'credits' => 'decimal:2',
        'total_credits_earned' => 'decimal:2',
        'total_credits_spent' => 'decimal:2',
        'last_credit_purchase_at' => 'datetime',
        'credits_expire_at' => 'datetime',
        'last_seen' => 'datetime',
    ];

    protected $appends = [
        'display_name',
        'profile_photo_url',
        'role_name',
        'is_online',
        'last_seen_for_humans',
    ];

    /* --------------------
       AUTO ONLINE LOGIC
       -------------------- */

    /**
     * Determine if the user is currently online.
     * Considers the user offline if last_seen > 5 minutes ago
     */
    public function getIsOnlineAttribute($value)
    {
        return $this->last_seen && $this->last_seen->gt(now()->subMinutes(1));
    }

    /**
     * Optional helper to get human-readable last seen
     */
    public function getLastSeenForHumansAttribute()
    {
        return $this->last_seen ? $this->last_seen->diffForHumans() : 'Never';
    }

    /* --------------------
       Relationships, Roles, etc.
       -------------------- */

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function town()
    {
        return $this->belongsTo(Town::class);
    }

    public function escortProfile()
    {
        return $this->hasOne(Escort::class);
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_picture;
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->hasRole('escort') && $this->escortProfile && $this->escortProfile->stage_name) {
            return $this->escortProfile->stage_name;
        }

        return $this->name;
    }

    public function conversations()
    {
        return Conversation::where('user_one_id', $this->id)
            ->orWhere('user_two_id', $this->id);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function mpesaPayments()
    {
        return $this->hasMany(MpesaPayment::class);
    }

    public function hasUserRole(string $role): bool
    {
        return $this->hasRole($role);
    }

    public function getRoleNameAttribute(): string
    {
        return $this->getRoleNames()->first() ?? 'member';
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isEscort(): bool
    {
        return $this->hasRole('escort');
    }

    public function isMember(): bool
    {
        return $this->hasRole('member');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function isVerified(): bool
    {
        if ($this->isEscort() && $this->escortProfile) {
            return $this->escortProfile->is_verified;
        }

        return $this->email_verified_at !== null && $this->phone_verified;
    }

    public function hasSufficientCredits($amount): bool
    {
        return $this->credits >= $amount;
    }

    public function addCredits($amount): bool
    {
        $this->credits += $amount;
        $this->total_credits_earned += $amount;
        return $this->save();
    }

    public function deductCredits($amount): bool
    {
        $this->credits -= $amount;
        $this->total_credits_spent += $amount;
        return $this->save();
    }
}

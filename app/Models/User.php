<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    public ?string $temp_password = null;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'user_type',
        'password',
        'phone_number',
        'phone_verified',
        'is_temp_password',
        'gender',
        'date_of_birth',
        'profile_picture',
        'county_id',
        'town_id',
        'location',
        'latitude',
        'longitude',
        'meta_data',
        'status',
        'last_seen',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified' => 'boolean',
        'is_temp_password' => 'boolean',
        'date_of_birth' => 'date',
        'age' => 'integer',
        'meta_data' => 'array',
        'last_seen' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = [
        'display_name',
        'profile_photo_url',
        'role_name',
        'is_online',
        'last_seen_for_humans',
        'age',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentName(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }

        return $this->name;
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return (int) $this->date_of_birth->age;
    }

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

    /**
     * The linked escort profile. Only present when user_type = 'escort'.
     */
    public function escortProfile()
    {
        return $this->hasOne(Escort::class);
    }

    /**
     * The linked member profile (credit wallet + social login).
     * Only present when user_type = 'member'.
     */
    public function memberProfile()
    {
        return $this->hasOne(Member::class);
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

    public function isSystemUser(): bool
    {
        return $this->user_type === 'system_user';
    }

    public function isEscort(): bool
    {
        return $this->user_type === 'escort';
    }

    public function isMember(): bool
    {
        return $this->user_type === 'member';
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
        return $this->memberProfile && $this->memberProfile->hasSufficientCredits($amount);
    }

    public function addCredits($amount): bool
    {
        return $this->memberProfile && $this->memberProfile->addCredits($amount);
    }

    public function deductCredits($amount): bool
    {
        return $this->memberProfile && $this->memberProfile->deductCredits($amount);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Member profile — holds the credit wallet and social-login associations.
 *
 * Linked 1:1 to User. Every user with user_type = 'member' should have
 * exactly one Member record. The credit wallet lives here, not on User.
 */
class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'credits',
        'total_credits_earned',
        'total_credits_spent',
        'last_credit_purchase_at',
        'credits_expire_at',
        'social_id',
        'social_provider',
        'social_avatar',
    ];

    protected $casts = [
        'credits' => 'decimal:2',
        'total_credits_earned' => 'decimal:2',
        'total_credits_spent' => 'decimal:2',
        'last_credit_purchase_at' => 'datetime',
        'credits_expire_at' => 'datetime',
    ];

    /* ── Relationships ── */

    /**
     * The user this member profile belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ── Wallet helpers ── */

    /**
     * Check whether the member has enough credits for a given spend.
     *
     * @param  float  $amount  The credit amount to check against the balance
     */
    public function hasSufficientCredits($amount): bool
    {
        return $this->credits >= $amount;
    }

    /**
     * Add credits to the wallet. Updates both the balance and the lifetime
     * earned total. A matching CreditTransaction should be written by the
     * caller.
     *
     * @param  float  $amount  Credits to add
     */
    public function addCredits($amount): bool
    {
        $this->credits += $amount;
        $this->total_credits_earned += $amount;

        return $this->save();
    }

    /**
     * Deduct credits from the wallet. Updates both the balance and the
     * lifetime spent total. A matching CreditTransaction should be written
     * by the caller.
     *
     * @param  float  $amount  Credits to deduct
     */
    public function deductCredits($amount): bool
    {
        $this->credits -= $amount;
        $this->total_credits_spent += $amount;

        return $this->save();
    }
}

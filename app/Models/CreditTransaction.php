<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function chatMessage()
    {
        return $this->hasOne(Message::class);
    }

    // Scope for purchases
    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    // Scope for usages
    public function scopeUsages($query)
    {
        return $query->where('type', 'usage');
    }

    // Scope for bonuses
    public function scopeBonuses($query)
    {
        return $query->where('type', 'bonus');
    }

    // Scope for withdrawals (escort payouts)
    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdrawal');
    }

    // Scope for escort commission earnings (escort's share of member spends)
    public function scopeCommissions($query)
    {
        return $query->where('type', 'commission');
    }

    // Scope for expired credits
    public function scopeExpiries($query)
    {
        return $query->where('type', 'expiry');
    }

    // Scope for refunds
    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }
}

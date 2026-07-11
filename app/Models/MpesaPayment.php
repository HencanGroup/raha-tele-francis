<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MpesaPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'reference',
        'amount',
        'credits_awarded',
        'phone_number',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credits_awarded' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creditTransaction()
    {
        return $this->hasOne(CreditTransaction::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    // Scope for pending payments
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope for completed payments
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}

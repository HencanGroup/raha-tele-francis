<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Escort withdrawal request — a request to convert accrued escort earnings
 * (credits) into a KES payout via M-Pesa B2C.
 *
 * Status lifecycle: pending -> processing -> completed | failed. The escort
 * wallet (Escort.balance) is reserved at request time and only refunded on
 * failure. Ledger entries are type 'withdrawal' (request) and 'refund' (failed).
 */
class Withdrawal extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Readable statuses the record can move through.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'amount',
        'amount_kes',
        'phone_number',
        'status',
        'mpesa_reference',
        'transaction_id',
        'failure_reason',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_kes' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /* ── Relationships ── */

    /**
     * The escort user who requested this withdrawal.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin user who approved/processed the payout (staff action).
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /* ── Scopes ── */

    /**
     * Withdrawals awaiting admin approval.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Withdrawals currently processing via the M-Pesa B2C call.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Successfully paid-out withdrawals.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Withdrawals that failed and were refunded.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}

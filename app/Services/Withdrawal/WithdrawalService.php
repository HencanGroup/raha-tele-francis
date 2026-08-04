<?php

namespace App\Services\Withdrawal;

use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Credit\CreditService;
use App\Services\MpesaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for escort credit withdrawals (M-Pesa B2C payouts).
 *
 * Lifecycle handled here:
 *   request()  — validate + reserve balance, write 'withdrawal' ledger entry.
 *   approve()  — mark processing and fire the Daraja B2C payout request.
 *   refund()   — restore the reserved balance on failure, write 'refund' entry.
 *   processB2CResult() — idempotent settlement from the Daraja callback.
 *
 * The escort's credits live on Escort.balance (there is no member wallet for
 * escorts). Every mutation is transactional and ledgered via CreditService.
 */
class WithdrawalService
{
    public function __construct(
        private readonly CreditService $creditService,
        private readonly MpesaService $mpesaService,
    ) {}

    /**
     * Create a pending withdrawal request for an escort.
     *
     * Validates the escort profile, the minimum amount, and the available
     * balance; reserves the credits by deducting escort.balance; computes the
     * KES payout (credits x credit_value_kes); and writes a 'withdrawal'
     * ledger entry reflecting the reserved balance.
     *
     * @param  User  $escortUser  The authenticated escort user.
     * @param  float  $amount  Credits to withdraw (>= minimum_withdrawal_credits).
     * @param  string  $phone  Recipient phone in Daraja format (2547...).
     *
     * @throws ValidationException When business rules are violated.
     */
    public function request(User $escortUser, float $amount, string $phone): Withdrawal
    {
        $escort = $escortUser->escortProfile;
        if (! $escort) {
            throw ValidationException::withMessages(['amount' => 'Escort profile not found.']);
        }

        $minimum = (float) config('system_settings.minimum_withdrawal_credits', 500);
        if ($amount < $minimum) {
            throw ValidationException::withMessages(['amount' => "Minimum withdrawal is {$minimum} credits."]);
        }

        if ((float) $escort->balance < $amount) {
            throw ValidationException::withMessages(['amount' => 'Insufficient balance for withdrawal.']);
        }

        $rate = (float) config('system_settings.credit_value_kes', 5);
        $amountKes = round($amount * $rate, 2);

        return DB::transaction(function () use ($escortUser, $escort, $amount, $amountKes, $phone) {
            // 1. Reserve the credits — deduct from the withdrawable balance.
            $escort->decrement('balance', $amount);

            // 2. Record the pending withdrawal.
            $withdrawal = Withdrawal::create([
                'user_id' => $escortUser->id,
                'amount' => $amount,
                'amount_kes' => $amountKes,
                'phone_number' => $phone,
                'status' => Withdrawal::STATUS_PENDING,
            ]);

            // 3. Ledger entry — balance_before reflects the pre-reservation balance.
            $this->creditService->writeLedger(
                $escortUser,
                type: 'withdrawal',
                amount: $amount,
                balanceBefore: (float) $escort->balance + $amount,
                balanceAfter: (float) $escort->balance,
                referenceType: Withdrawal::class,
                referenceId: $withdrawal->id,
                description: 'Withdrawal request #'.$withdrawal->id,
            );

            return $withdrawal;
        });
    }

    /**
     * Approve a pending withdrawal — marks it processing and fires the M-Pesa
     * B2C payout. Final settlement is handled by the B2C result callback.
     *
     * @throws \RuntimeException When the withdrawal is not pending.
     */
    public function approve(Withdrawal $withdrawal, User $admin): void
    {
        // Idempotency guard — only a pending request can be approved, which
        // also protects against double-clicks racing the B2C call.
        if ($withdrawal->status !== Withdrawal::STATUS_PENDING) {
            throw new \RuntimeException('Only pending withdrawals can be approved.');
        }

        $withdrawal->update([
            'status' => Withdrawal::STATUS_PROCESSING,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        try {
            $response = $this->mpesaService->sendB2CPayout($withdrawal);

            // Store OriginatorConversationID so the result callback can match us.
            $withdrawal->update([
                'mpesa_reference' => $response['OriginatorConversationID'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // The Daraja request itself failed — refund the reserved credits.
            $this->refund($withdrawal, 'M-Pesa payout failed: '.$e->getMessage());
        }
    }

    /**
     * Handle a Daraja B2C result/timeout callback payload.
     *
     * Matches the withdrawal by OriginatorConversationID and settles it.
     * Idempotent — already-completed withdrawals are never re-processed.
     *
     * @param  string  $originatorConversationId  OriginatorConversationID from the callback.
     * @param  int  $resultCode  Daraja result code (0 = success).
     * @param  string|null  $transactionId  M-Pesa transaction (receipt) ID.
     * @param  string|null  $resultDesc  Human-readable Daraja result description.
     */
    public function processB2CResult(
        string $originatorConversationId,
        int $resultCode,
        ?string $transactionId,
        ?string $resultDesc,
    ): void {
        $withdrawal = Withdrawal::where('mpesa_reference', $originatorConversationId)->first();

        // Unknown reference — this is a public callback, so ignore it safely.
        if (! $withdrawal) {
            return;
        }

        // Never double-process an already-settled payout.
        if ($withdrawal->status === Withdrawal::STATUS_COMPLETED) {
            return;
        }

        if ($resultCode === 0) {
            $withdrawal->update([
                'status' => Withdrawal::STATUS_COMPLETED,
                'transaction_id' => $transactionId,
                'processed_at' => now(),
            ]);

            return;
        }

        $this->refund($withdrawal, $resultDesc ?: 'M-Pesa result code '.$resultCode);
    }

    /**
     * Refund a failed/cancelled withdrawal — restores the reserved credits to
     * the escort balance and writes a 'refund' ledger entry.
     *
     * Idempotent: only pending/processing withdrawals can be refunded, so a
     * re-run (or a late callback) can never double-credit the escort.
     */
    public function refund(Withdrawal $withdrawal, string $reason): void
    {
        DB::transaction(function () use ($withdrawal, $reason): void {
            if (! in_array($withdrawal->status, [Withdrawal::STATUS_PENDING, Withdrawal::STATUS_PROCESSING], true)) {
                return;
            }

            $escort = $withdrawal->user?->escortProfile;
            $balanceBefore = (float) ($escort?->balance ?? 0);

            $escort?->increment('balance', (float) $withdrawal->amount);

            $withdrawal->update([
                'status' => Withdrawal::STATUS_FAILED,
                'failure_reason' => $reason,
                'processed_at' => now(),
            ]);

            if ($withdrawal->user) {
                $this->creditService->writeLedger(
                    $withdrawal->user,
                    type: 'refund',
                    amount: (float) $withdrawal->amount,
                    balanceBefore: $balanceBefore,
                    balanceAfter: $balanceBefore + (float) $withdrawal->amount,
                    referenceType: Withdrawal::class,
                    referenceId: $withdrawal->id,
                    description: 'Refund for failed withdrawal #'.$withdrawal->id,
                );
            }
        });
    }
}

<?php

namespace App\Services\Credit;

use App\Models\CreditTransaction;
use App\Models\Escort;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Central credit-wallet and ledger operations.
 *
 * All wallet movements (member spends, escort earnings, expiry) route through
 * this service so that every mutation writes an immutable CreditTransaction
 * ledger entry with matching balance_before / balance_after, per the ledger
 * rules in AGENTS.md. Callers never mutate a wallet directly.
 *
 * spendCredits() and creditEscort() assume they run inside an outer
 * DB::transaction — the caller owns message creation, escrow, etc. Only
 * expireExpiredCredits() wraps its own per-wallet transactions.
 */
class CreditService
{
    /**
     * Deduct credits from a member's wallet and write a 'usage' ledger entry.
     *
     * The member wallet and ledger row share one deduction; the caller is
     * responsible for the surrounding transaction and idempotency guard.
     *
     * @param  User  $user  The member spending credits.
     * @param  float  $amount  Credits deducted.
     * @param  string  $referenceType  Morph class the ledger entry points at.
     * @param  int  $referenceId  Morph id the ledger entry points at.
     * @param  string  $description  Human-readable ledger description.
     * @param  array<string, mixed>  $metadata  Optional JSON metadata for the ledger row.
     */
    public function spendCredits(
        User $user,
        float $amount,
        string $referenceType,
        int $referenceId,
        string $description,
        array $metadata = [],
    ): CreditTransaction {
        $member = $user->memberProfile;
        $balanceBefore = (float) ($member->credits ?? 0);
        $balanceAfter = $balanceBefore - $amount;

        $user->deductCredits($amount);

        return $this->writeLedger(
            $user,
            type: 'usage',
            amount: $amount,
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceAfter,
            referenceType: $referenceType,
            referenceId: $referenceId,
            description: $description,
            metadata: $metadata,
        );
    }

    /**
     * Credit an escort's lifetime earnings and withdrawable balance, and write
     * a 'commission' ledger row against the escort's own user_id.
     *
     * The ledger row is what powers GET /api/earnings/transactions — without
     * it, escorts see an empty history because the member's 'usage' row is
     * written against the spender, not the escort. Balance before/after
     * reflect Escort.balance (withdrawable credits), matching WithdrawalService
     * semantics so a commission row and a later withdrawal row reconcile.
     *
     * @param  Escort  $escort  The escort receiving their share.
     * @param  float  $amount  Escort share of the spend (credits).
     * @param  string  $referenceType  Morph class the commission points at (Escort/Message/…).
     * @param  int  $referenceId  Morph id the commission points at.
     * @param  string  $description  Human-readable ledger description.
     * @param  array<string, mixed>  $metadata  Optional JSON metadata for the ledger row.
     */
    public function creditEscort(
        Escort $escort,
        float $amount,
        string $referenceType,
        int $referenceId,
        string $description,
        array $metadata = [],
    ): void {
        $balanceBefore = (float) ($escort->balance ?? 0);

        $escort->increment('earnings', $amount);
        $escort->increment('balance', $amount);

        $this->writeLedger(
            $escort->user,
            type: 'commission',
            amount: $amount,
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceBefore + $amount,
            referenceType: $referenceType,
            referenceId: $referenceId,
            description: $description,
            metadata: $metadata,
        );
    }

    /**
     * Zero an expired member wallet and write an 'expiry' ledger entry.
     *
     * Idempotent by construction — an already-expired wallet (credits = 0)
     * is left untouched, so a re-run of the expiry job cannot double-expire.
     *
     * @param  Member  $member  The member whose wallet is expiring.
     * @param  float  $amount  Credits being expired (must equal the balance).
     */
    public function expireCredits(Member $member, float $amount): void
    {
        if ((float) ($member->credits ?? 0) <= 0) {
            return;
        }

        $balanceBefore = (float) $member->credits;

        $member->update([
            'credits' => 0,
            'credits_expire_at' => null,
        ]);

        $this->writeLedger(
            $member->user,
            type: 'expiry',
            amount: $balanceBefore,
            balanceBefore: $balanceBefore,
            balanceAfter: 0,
            referenceType: Member::class,
            referenceId: $member->id,
            description: 'Credits expired',
        );
    }

    /**
     * Expire every member wallet whose credits_expire_at has passed.
     *
     * Runs each wallet in its own transaction so a failure never rolls back
     * other members' expiries. Returns the number of wallets expired.
     */
    public function expireExpiredCredits(): int
    {
        $expired = Member::query()
            ->where('credits', '>', 0)
            ->whereNotNull('credits_expire_at')
            ->where('credits_expire_at', '<=', now())
            ->get();

        foreach ($expired as $member) {
            DB::transaction(function () use ($member): void {
                $this->expireCredits($member, (float) $member->credits);
            });
        }

        return $expired->count();
    }

    /**
     * Write an immutable ledger row. This is the only place CreditTransaction
     * rows are created; all balance movements flow through here.
     *
     * @param  User  $user  The user whose ledger this row belongs to.
     * @param  string  $type  Ledger type (purchase, usage, bonus, withdrawal, expiry, refund).
     * @param  float  $amount  Absolute amount moved.
     * @param  float  $balanceBefore  Wallet/balance before the movement.
     * @param  float  $balanceAfter  Wallet/balance after the movement.
     * @param  string  $referenceType  Morph class for the ledger reference.
     * @param  int  $referenceId  Morph id for the ledger reference.
     * @param  string  $description  Human-readable ledger description.
     * @param  array<string, mixed>  $metadata  Optional JSON metadata for the ledger row.
     */
    public function writeLedger(
        User $user,
        string $type,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $referenceType,
        int $referenceId,
        string $description,
        array $metadata = [],
    ): CreditTransaction {
        return CreditTransaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }
}

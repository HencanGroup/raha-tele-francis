<?php

namespace App\Services\Escort;

use App\Models\CreditTransaction;
use App\Models\Escort;
use App\Models\User;
use App\Services\Commission\CommissionService;
use App\Services\Credit\CreditService;
use Illuminate\Support\Facades\DB;

/**
 * Service handling credit deduction, commission splitting, and ledger
 * entry for phone number unlocks.
 *
 * Commission split is delegated to CommissionService (reads
 * config('system_settings.platform_commission_percent') — default 30%
 * platform / 70% escort). Wallet/ledger movements delegate to CreditService.
 * Every credit movement is wrapped in a DB transaction and writes an
 * immutable CreditTransaction ledger entry.
 */
class PhoneUnlockService
{
    public function __construct(
        private readonly CommissionService $commissionService,
        private readonly CreditService $creditService,
    ) {}

    /**
     * Whether the member has already paid to unlock this escort's phone number.
     *
     * Reads the immutable ledger — every unlock writes exactly one 'usage'
     * row referencing the escort, so its existence means "paid". Chat spends
     * reference Message::class, so a chat payment can never false-positive.
     *
     * @param  User  $user  The member to check.
     * @param  Escort  $escort  The escort whose phone is being unlocked.
     */
    public function hasUnlockedPhone(User $user, Escort $escort): bool
    {
        return CreditTransaction::where('user_id', $user->id)
            ->where('type', 'usage')
            ->where('reference_type', Escort::class)
            ->where('reference_id', $escort->id)
            ->exists();
    }

    /**
     * Process a phone unlock credit flow:
     *
     * 0. No-op when the member already unlocked this escort (idempotency —
     *    a repeat request can never charge twice)
     * 1. Deduct credits from the member wallet
     * 2. Write a 'usage' CreditTransaction referencing the escort
     * 3. Split commission: credit the escort's earnings/balance and record
     *    the platform's cut explicitly ('platform_commission' ledger row)
     */
    public function unlock(User $user, Escort $escort): void
    {
        // Idempotency guard — an already-paid unlock is a free no-op, so a
        // double-submit or repeat API call never double-charges the wallet.
        if ($this->hasUnlockedPhone($user, $escort)) {
            return;
        }

        $cost = (float) config('services.system_variables.phone_unlock_cost', 10);
        $split = $this->commissionService->split($cost);

        DB::transaction(function () use ($user, $escort, $cost, $split): void {
            // 1-2. Deduct from member wallet and write the usage ledger entry.
            $this->creditService->spendCredits(
                $user,
                $cost,
                Escort::class,
                $escort->id,
                'Phone unlock for escort #'.$escort->id,
            );

            // 3. Credit the escort's earnings (escort share of cost) and write
            //    the escort's own 'commission' ledger row.
            $this->creditService->creditEscort(
                $escort,
                $split['escort'],
                Escort::class,
                $escort->id,
                'Commission for phone unlock #'.$escort->id,
            );

            // 4. Record the platform's cut explicitly — powers the admin
            //    Platform Earnings widget straight from the ledger.
            $this->creditService->writePlatformCommission(
                $split['platform'],
                Escort::class,
                $escort->id,
                'Platform commission for phone unlock #'.$escort->id,
            );
        });
    }
}

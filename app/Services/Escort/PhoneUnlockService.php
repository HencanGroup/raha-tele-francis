<?php

namespace App\Services\Escort;

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
     * Process a phone unlock credit flow:
     *
     * 1. Deduct credits from the member wallet
     * 2. Write a 'usage' CreditTransaction referencing the escort
     * 3. Split commission and credit the escort's earnings/balance
     */
    public function unlock(User $user, Escort $escort): void
    {
        $cost = (float) config('services.system_variables.phone_unlock_cost', 10);
        $escortShare = $this->commissionService->escortShare($cost);

        DB::transaction(function () use ($user, $escort, $cost, $escortShare): void {
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
                $escortShare,
                Escort::class,
                $escort->id,
                'Commission for phone unlock #'.$escort->id,
            );
        });
    }
}

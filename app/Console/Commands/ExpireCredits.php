<?php

namespace App\Console\Commands;

use App\Services\Credit\CreditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Expire member credit balances whose credits_expire_at has passed.
 *
 * Runs daily via the Laravel scheduler (routes/console.php). Every expired
 * wallet is zeroed and written to the ledger with a type of 'expiry' by
 * CreditService::expireExpiredCredits() — the ledger is the source of truth.
 */
class ExpireCredits extends Command
{
    /**
     * Artisan signature — also referenced by the scheduler.
     */
    protected $signature = 'credits:expire';

    /**
     * Command description shown in `php artisan list`.
     */
    protected $description = 'Expire member credit balances past credits_expire_at and write expiry ledger entries.';

    /**
     * Run the expiry job across all eligible member wallets.
     */
    public function handle(CreditService $creditService): int
    {
        $expired = $creditService->expireExpiredCredits();

        if ($expired > 0) {
            Log::info('ExpireCredits: expired credits', ['members_expired' => $expired]);
        }

        $this->info("Expired credits for {$expired} member wallet(s).");

        return self::SUCCESS;
    }
}

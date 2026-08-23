<?php

namespace App\Console\Commands;

use App\Models\CreditTransaction;
use App\Services\Credit\CreditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BackfillPlatformCommissions
 * -----------------------------------------------------------------------------
 * Writes 'platform_commission' ledger rows for spends that predate explicit
 * platform recording.
 *
 * Legacy spend flows wrote only a member 'usage' row and an escort
 * 'commission' row, so the platform's cut existed only implicitly. This
 * command aggregates each reference group (reference_type + reference_id)
 * and inserts one platform row per group worth SUM(usage) − SUM(commission)
 * — the exact amount that left member wallets but never reached escorts.
 *
 * Idempotent — reference groups that already contain any
 * 'platform_commission' row are skipped, so re-running never double-records.
 *
 * Public entrypoint: handle()
 */
class BackfillPlatformCommissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credit-transactions:backfill-platform-commissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill implicit platform commission ledger rows for legacy spends';

    /**
     * Orchestrates the backfill: find unrecorded groups → insert one
     * aggregated platform row per group → report.
     */
    public function handle(CreditService $creditService): int
    {
        // 1. Find reference groups that have member spends but no explicit
        //    platform row yet.
        $groups = $this->findUnrecordedGroups();

        if ($groups->isEmpty()) {
            Log::info('BackfillPlatformCommissions: nothing to backfill');
            $this->info('  ✓ Every spend group already has a platform_commission row.');

            return self::SUCCESS;
        }

        $this->info("  → Found {$groups->count()} spend group(s) without platform records.");

        // 2. Insert one aggregated platform row per group.
        $inserted = 0;

        foreach ($groups as $group) {
            $inserted += $this->backfillGroup($group, $creditService);
        }

        // 3. Report.
        Log::info('BackfillPlatformCommissions: completed', ['rows' => $inserted]);
        $this->info("  ✓ Backfilled {$inserted} platform_commission row(s).");

        return self::SUCCESS;
    }

    /**
     * Finds every (reference_type, reference_id) group that contains at least
     * one member 'usage' spend but no 'platform_commission' row.
     *
     * @return \Illuminate\Support\Collection<int, object{reference_type: string, reference_id: int}>
     */
    protected function findUnrecordedGroups(): \Illuminate\Support\Collection
    {
        return CreditTransaction::query()
            ->where('type', 'usage')
            ->whereNotNull('reference_type')
            ->groupBy('reference_type', 'reference_id')
            ->select('reference_type', 'reference_id')
            ->get()
            // Idempotency guard — skip groups already recorded explicitly.
            ->reject(
                fn ($group) => CreditTransaction::query()
                    ->where('type', 'platform_commission')
                    ->where('reference_type', $group->reference_type)
                    ->where('reference_id', $group->reference_id)
                    ->exists()
            );
    }

    /**
     * Aggregates one reference group and inserts its platform commission row.
     *
     * Platform take = SUM(usage) − SUM(commission): everything members spent
     * on this reference minus what escorts actually received.
     *
     * @param  object{reference_type: string, reference_id: int}  $group
     */
    protected function backfillGroup(object $group, CreditService $creditService): int
    {
        $spent = (float) CreditTransaction::query()
            ->where('type', 'usage')
            ->where('reference_type', $group->reference_type)
            ->where('reference_id', $group->reference_id)
            ->sum('amount');

        $credited = (float) CreditTransaction::query()
            ->where('type', 'commission')
            ->where('reference_type', $group->reference_type)
            ->where('reference_id', $group->reference_id)
            ->sum('amount');

        $platform = round($spent - $credited, 2);

        if ($platform <= 0) {
            $this->warn("  ↻ Skipping {$group->reference_type}#{$group->reference_id} — no positive platform share ({$platform}).");

            return 0;
        }

        DB::transaction(function () use ($creditService, $group, $platform): void {
            $creditService->writePlatformCommission(
                $platform,
                $group->reference_type,
                (int) $group->reference_id,
                "Backfilled platform commission for {$group->reference_type}#{$group->reference_id}",
            );
        });

        $this->info("  + Recorded {$platform} credits → {$group->reference_type}#{$group->reference_id}");

        return 1;
    }
}

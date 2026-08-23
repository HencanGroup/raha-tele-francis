<?php

namespace Database\Seeders;

use App\Models\CreditTransaction;
use App\Models\Escort;
use App\Models\User;
use App\Services\Commission\CommissionService;
use App\Services\Credit\CreditService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TransactionSeeder
 * -----------------------------------------------------------------------------
 * Seeds sample credit transactions from database/data/transactions.json.
 *
 * Data source: database/data/transactions.json
 * Every record describes a member spending credits on an escort (phone unlock
 * or paid message). The seeder routes each spend through the real credit and
 * commission services so the ledger, member wallet, and escort earnings stay
 * consistent: the member pays the full amount, the platform keeps its
 * commission share, and the escort earns the remainder (default 70%).
 *
 * Idempotent — skips a spend when the member already has a matching 'usage'
 * ledger row, so re-running `php artisan db:seed` never double-charges a
 * wallet or double-credits an escort.
 *
 * Public entrypoint: run()
 * All business logic is delegated to the protected helpers below so the
 * run() method reads as a high-level orchestration script.
 */
class TransactionSeeder extends Seeder
{
    /**
     * Relative path (from database/) to the transaction data file.
     */
    protected const DATA_FILE = 'data/transactions.json';

    public function __construct(
        private readonly CommissionService $commissionService,
        private readonly CreditService $creditService,
    ) {}

    /**
     * Orchestrates the seeding flow: load data file → process each spend.
     */
    public function run(): void
    {
        // 1. Load the full transaction records from the JSON data file.
        $transactions = $this->loadTransactions();

        // 2. Process every spend through the credit + commission services.
        foreach ($transactions as $record) {
            $this->processSpend($record);
        }
    }

    /**
     * Loads and returns the transaction array from the JSON data file.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function loadTransactions(): array
    {
        $path = database_path(self::DATA_FILE);

        Log::info('TransactionSeeder: loading transactions', ['path' => $path]);
        $this->command->info("  → Loading transactions from {$path}");

        return json_decode(file_get_contents($path), true);
    }

    /**
     * Routes a single member spend through the commission split, member
     * wallet deduction, and escort earnings credit — all inside one DB
     * transaction. Skips the spend if it was already seeded.
     *
     * @param  array<string, mixed>  $record
     */
    protected function processSpend(array $record): void
    {
        $member = User::where('email', $record['member_email'])->where('user_type', 'member')->first();
        $escort = User::where('email', $record['escort_email'])
            ->where('user_type', 'escort')
            ->first()
            ?->escortProfile;

        if (! $member || ! $escort) {
            Log::warning('TransactionSeeder: skipping spend — member/escort not found', [
                'member' => $record['member_email'],
                'escort' => $record['escort_email'],
            ]);
            $this->command->warn("  ✗ Skipping spend → {$record['member_email']} on {$record['escort_email']} (user not found)");

            return;
        }

        // Idempotency guard — skip if this member already spent this amount
        // on this escort, so re-seeding never double-charges or double-credits.
        $alreadySpent = CreditTransaction::where('user_id', $member->id)
            ->where('type', 'usage')
            ->where('reference_type', Escort::class)
            ->where('reference_id', $escort->id)
            ->where('amount', $record['amount'])
            ->exists();

        if ($alreadySpent) {
            Log::info('TransactionSeeder: spend already seeded, skipping', [
                'member' => $member->email,
                'escort' => $escort->id,
                'amount' => $record['amount'],
            ]);
            $this->command->warn("  ↻ Spend already seeded → {$member->email} on escort #{$escort->id}");

            return;
        }

        $amount = (float) $record['amount'];
        $split = $this->commissionService->split($amount);

        DB::transaction(function () use ($member, $escort, $amount, $split, $record): void {
            // 1-2. Deduct from the member wallet and write the 'usage' ledger
            //      row, recording the platform/escort split in metadata.
            $this->creditService->spendCredits(
                $member,
                $amount,
                Escort::class,
                $escort->id,
                $record['description'] ?? 'Spend on escort #'.$escort->id,
                [
                    'escort_email' => $escort->user->email,
                    'platform_share' => $split['platform'],
                    'escort_share' => $split['escort'],
                    'commission_percent' => $this->commissionService->platformPercent(),
                ],
            );

            // 3. Credit the escort's earnings (escort share) and write their
            //    own 'commission' ledger row.
            $this->creditService->creditEscort(
                $escort,
                $split['escort'],
                Escort::class,
                $escort->id,
                'Commission for spend by '.$member->email,
            );

            // 4. Record the platform's commission share explicitly so seeded
            //    data matches what the live spend flows produce.
            $this->creditService->writePlatformCommission(
                $split['platform'],
                Escort::class,
                $escort->id,
                'Platform commission for spend by '.$member->email,
            );
        });

        Log::info('TransactionSeeder: created spend', [
            'member' => $member->email,
            'escort' => $escort->id,
            'amount' => $amount,
            'escort_share' => $split['escort'],
            'platform_share' => $split['platform'],
        ]);
        $this->command->info(
            "  + Created spend → {$member->email} on escort #{$escort->id} ({$amount} credits; escort earns {$split['escort']}, platform keeps {$split['platform']})"
        );
    }
}

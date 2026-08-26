<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder
 * -----------------------------------------------------------------------------
 * Top-level orchestrator. This class does no work itself — it only delegates
 * to the panel-specific seeders below in the correct order.
 *
 * Run with:
 *   php artisan migrate:fresh --seed   (clean slate)
 *   php artisan db:seed                (idempotent re-run; safe on existing DB)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ordered list of seeders to invoke.
     *
     * Order matters:
     *   1. KenyaCountiesSeeder   — location reference data (counties + towns).
     *   2. RoleSeeder            — Spatie roles (super_admin) + cache flush.
     *   3. MemberSeeder          — members from database/data/members.json.
     *   4. EscortSeeder          — escorts from database/data/escorts.json.
     *   5. TransactionSeeder     — sample member spends on escorts.
     *   6. AdminUserSeeder       — super-admin from admin-users.json.
     */
    protected array $seeders = [
        KenyaCountiesSeeder::class,
        RoleSeeder::class,
            // MemberSeeder::class,
        EscortSeeder::class,
            // TransactionSeeder::class,
        AdminUserSeeder::class,
        SystemSettingSeeder::class,
    ];

    /**
     * Run each registered seeder, with a dynamic delimiter around it so the
     * boundaries between seeders are easy to spot in the console output.
     */
    public function run(): void
    {
        $total = count($this->seeders);

        foreach ($this->seeders as $index => $seederClass) {
            $this->runSeeder($seederClass, $index + 1, $total);
        }
    }

    /**
     * Invokes a single seeder and prints dynamic progress lines around it.
     * Only the seeder name and progress counter are echoed — no static info.
     */
    protected function runSeeder(string $seederClass, int $position, int $total): void
    {
        $name = class_basename($seederClass);

        $this->command->line('');
        $this->command->info("▶ [{$position}/{$total}] Running {$name}...");

        $this->call($seederClass);

        $this->command->info("✓ [{$position}/{$total}] Finished {$name}");
    }
}

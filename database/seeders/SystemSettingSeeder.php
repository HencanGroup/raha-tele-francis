<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * SystemSettingSeeder
 * -----------------------------------------------------------------------------
 * Seeds the default platform configuration variables into the system_settings
 * table. These values are loaded into config('system_settings.*') at boot and
 * override the file defaults in config/system_settings.php.
 *
 * Data source: the defaults defined in config/system_settings.php.
 *
 * Idempotent — skips existing keys, so re-running is safe.
 *
 * Public entrypoint: run()
 * All business logic is delegated to the protected helpers below so the
 * run() method reads as a high-level orchestration script.
 */
class SystemSettingSeeder extends Seeder
{
    /**
     * Default settings keyed by setting name with their typed values.
     */
    protected const DEFAULTS = [
        'platform_commission_percent' => ['value' => 30, 'type' => 'integer'],
        'minimum_withdrawal_credits' => ['value' => 500, 'type' => 'integer'],
        'credit_expiry_days' => ['value' => 365, 'type' => 'integer'],
        'phone_unlock_cost' => ['value' => 5, 'type' => 'integer'],
        'message_cost' => ['value' => 1, 'type' => 'integer'],
    ];

    /**
     * Orchestrates the seeding flow: iterate defaults → upsert each → report.
     */
    public function run(): void
    {
        // 1. Iterate every default setting and upsert it.
        foreach (self::DEFAULTS as $key => $config) {
            $this->upsertSetting($key, $config['value'], $config['type']);
        }

        // 2. Report the total seeded.
        $this->reportResults(count(self::DEFAULTS));
    }

    /**
     * Creates a setting row or skips if the key already exists.
     *
     * @param  string  $key  Setting key (e.g. 'platform_commission_percent')
     * @param  mixed  $value  Default value
     * @param  string  $type  Value type (string/integer/decimal/boolean)
     */
    protected function upsertSetting(string $key, mixed $value, string $type): void
    {
        if (SystemSetting::where('key', $key)->exists()) {
            Log::info('SystemSettingSeeder: key already exists, skipping', ['key' => $key]);
            $this->command->warn("  ↻ Setting {$key} already exists. Skipping...");

            return;
        }

        SystemSetting::create([
            'key' => $key,
            'value' => (string) $value,
            'type' => $type,
        ]);

        Log::info('SystemSettingSeeder: created setting', ['key' => $key, 'value' => $value]);
        $this->command->info("  + Created setting → {$key} = {$value}");
    }

    /**
     * Logs and displays a summary of how many settings were seeded.
     */
    protected function reportResults(int $count): void
    {
        Log::info('SystemSettingSeeder: completed', ['seeded' => $count]);
        $this->command->info("  ✓ Seeded {$count} default settings.");
    }
}

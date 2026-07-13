<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Town;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * KenyaCountiesSeeder
 * -----------------------------------------------------------------------------
 * Seeds all 47 Kenyan counties and their major towns from a JSON definition
 * file.
 *
 * Data source: database/data/kenya-counties.json
 * Each record contains the county name, its KNBS code, and an array of major
 * town names. One County row and several Town rows are created per record.
 *
 * Idempotent — uses firstOrCreate() semantics via existence checks, so
 * re‑running `php artisan db:seed` is safe (existing rows are skipped,
 * new rows are inserted).
 *
 * Public entrypoint: run()
 * All business logic is delegated to the protected helpers below so the
 * run() method reads as a high‑level orchestration script.
 */
class KenyaCountiesSeeder extends Seeder
{
    /**
     * Relative path (from database/) to the county data file.
     */
    protected const DATA_FILE = 'data/kenya-counties.json';

    /**
     * Orchestrates the seeding flow: load data → create counties with
     * towns → display a summary.
     */
    public function run(): void
    {
        // 1. Load all counties from the JSON data file.
        $counties = $this->loadCounties();

        // 2. Show a progress bar for the 47 counties.
        $this->command->info('Seeding Kenyan counties and towns...');
        $this->command->getOutput()->progressStart(count($counties));

        // 3. Create each county and its associated towns.
        foreach ($counties as $countyData) {
            $this->createCountyWithTowns($countyData);
            $this->command->getOutput()->progressAdvance();
        }

        // 4. Finish the progress bar and report the result.
        $this->command->getOutput()->progressFinish();
        $this->reportResults(count($counties));
    }

    /**
     * Loads and returns the county array from the JSON data file.
     *
     * @return array<int, array{name: string, code: string, towns: string[]}>
     */
    protected function loadCounties(): array
    {
        $path = database_path(self::DATA_FILE);

        Log::info('KenyaCountiesSeeder: loading counties', ['path' => $path]);
        $this->command->info("  → Loading from {$path}");

        return json_decode(file_get_contents($path), true);
    }

    /**
     * Creates one County row and its child Town rows.
     *
     * @param  array{name: string, code: string, towns: string[]}  $countyData
     */
    protected function createCountyWithTowns(array $countyData): void
    {
        $county = County::create([
            'name' => $countyData['name'],
            'code' => $countyData['code'],
        ]);

        foreach ($countyData['towns'] as $townName) {
            Town::create([
                'name' => $townName,
                'county_id' => $county->id,
            ]);
        }

        Log::info('KenyaCountiesSeeder: created county', [
            'county' => $countyData['name'],
            'towns' => count($countyData['towns']),
        ]);
    }

    /**
     * Logs and displays a summary of how many counties were seeded.
     */
    protected function reportResults(int $count): void
    {
        Log::info('KenyaCountiesSeeder: completed', ['counties' => $count]);
        $this->command->info("  ✓ Seeded {$count} counties with their towns.");
    }
}

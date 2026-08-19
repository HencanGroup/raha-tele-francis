<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Member;
use App\Models\Town;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * MemberSeeder
 * -----------------------------------------------------------------------------
 * Seeds member profiles from database/data/members.json.
 *
 * Data source: database/data/members.json
 * Every record in the file is a full, distinct member profile (identity,
 * location, and starting credit wallet). The seeder seeds those records
 * verbatim, so the JSON is the single source of truth for demo members.
 *
 * Idempotent — skips existing emails with a warning via existence checks,
 * so re-running `php artisan db:seed` never duplicates records.
 *
 * Public entrypoint: run()
 * All business logic is delegated to the protected helpers below so the
 * run() method reads as a high-level orchestration script.
 */
class MemberSeeder extends Seeder
{
    /**
     * Relative path (from database/) to the member data file.
     */
    protected const DATA_FILE = 'data/members.json';

    /**
     * Orchestrates the seeding flow: load data file → ensure locations →
     * create each member with a credit wallet.
     */
    public function run(): void
    {
        // 1. Load the full member records from the JSON data file.
        $members = $this->loadMembers();

        // 2. Load location reference data (must exist for foreign keys).
        $this->ensureLocationsExist();

        $counties = County::all();
        $towns = Town::all();

        // 3. Create every member verbatim from the JSON records.
        foreach ($members as $record) {
            $this->createMember($record, $counties, $towns);
        }
    }

    /**
     * Loads and returns the member array from the JSON data file.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function loadMembers(): array
    {
        $path = database_path(self::DATA_FILE);

        Log::info('MemberSeeder: loading members', ['path' => $path]);
        $this->command->info("  → Loading members from {$path}");

        return json_decode(file_get_contents($path), true);
    }

    /**
     * Verifies that county and town records already exist (seeded by
     * KenyaCountiesSeeder) and aborts early if either collection is empty.
     */
    protected function ensureLocationsExist(): void
    {
        $counties = County::all();
        $towns = Town::all();

        if ($counties->isEmpty()) {
            $this->command->error('  ✗ No counties found. Run KenyaCountiesSeeder first.');

            return;
        }

        if ($towns->isEmpty()) {
            $this->command->error('  ✗ No towns found. Run KenyaCountiesSeeder first.');

            return;
        }

        $this->command->info("  ✓ Found {$counties->count()} counties and {$towns->count()} towns.");
    }

    /**
     * Creates one member user verbatim from a full JSON record, together
     * with the linked Member credit wallet. Every field in the record maps
     * to a User + Member column.
     *
     * @param  array<string, mixed>  $record
     * @param  \Illuminate\Database\Eloquent\Collection  $counties
     * @param  \Illuminate\Database\Eloquent\Collection  $towns
     */
    protected function createMember(array $record, $counties, $towns): void
    {
        $email = $record['email'];

        if (User::where('email', $email)->exists()) {
            Log::info('MemberSeeder: member already exists, skipping', ['email' => $email]);
            $this->command->warn("  ↻ Member {$email} already exists. Skipping...");

            return;
        }

        $county = $counties->where('name', $record['county_name'])->first();
        $countyTowns = $towns->where('county_id', $county ? $county->id : null);

        $user = User::create([
            'name' => $record['name'],
            'first_name' => $record['first_name'],
            'last_name' => $record['last_name'],
            'email' => $email,
            'password' => Hash::make($record['password'] ?? 'password123'),
            'email_verified_at' => now(),
            'phone_number' => $record['phone_number'],
            'phone_verified' => $record['phone_verified'] ?? true,
            'gender' => $record['gender'],
            'date_of_birth' => $record['date_of_birth'],
            'location' => $record['location'],
            'county_id' => $county ? $county->id : null,
            'town_id' => $countyTowns->isNotEmpty() ? $countyTowns->first()->id : null,
            'latitude' => $record['latitude'],
            'longitude' => $record['longitude'],
            'user_type' => 'member',
            'status' => $record['status'] ?? 'active',
        ]);

        Member::create([
            'user_id' => $user->id,
            'credits' => $record['credits'] ?? 0,
            'total_credits_earned' => $record['total_credits_earned'] ?? ($record['credits'] ?? 0),
            'total_credits_spent' => $record['total_credits_spent'] ?? 0,
        ]);

        Log::info('MemberSeeder: created member', ['email' => $email]);
        $this->command->info("  + Created member → {$user->email}");
    }
}

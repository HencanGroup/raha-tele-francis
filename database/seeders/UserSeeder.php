<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Escort;
use App\Models\EscortResource;
use App\Models\Favorite;
use App\Models\Member;
use App\Models\Review;
use App\Models\Town;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

/**
 * UserSeeder
 * -----------------------------------------------------------------------------
 * Seeds demo users — admins, members, and escorts — with realistic but
 * randomised profiles, credit wallets, and sample data.
 *
 * Data source: database/data/escorts.json
 * All reference arrays (names, stage names, bios, review comments, etc.)
 * are collated from this single file so the seeder stays focused on
 * generation and insertion logic.
 *
 * Idempotent — skips existing emails with a warning via existence checks,
 * so re‑running `php artisan db:seed` never duplicates records.
 *
 * Public entrypoint: run()
 * High‑level steps delegate to private helpers for each user group.
 */
class UserSeeder extends Seeder
{
    /**
     * Relative path (from database/) to the combined reference data file.
     */
    protected const DATA_FILE = 'data/escorts.json';

    /**
     * Number of demo member users to create.
     */
    protected const MEMBER_COUNT = 10;

    /**
     * Number of demo escort users to create.
     */
    protected const ESCORT_COUNT = 30;

    /* ── Reference arrays (populated at the top of run()) ── */

    private array $firstNames = [];

    private array $lastNames = [];

    private array $escortStageNames = [];

    private array $specialFeatures = [];

    private array $languages = [];

    private array $bioTemplates = [];

    private array $reviewComments = [];

    private array $interests = [];

    private array $seedAdmins = [];

    /**
     * Orchestrates the seeding flow: load reference data → load locations →
     * create each user group → attach sample data.
     */
    public function run(): void
    {
        // 1. Load all reference arrays from the single JSON data file.
        $this->loadReferenceData();

        // 2. Load location reference data (must exist for foreign keys).
        $this->ensureLocationsExist();

        $counties = County::all();
        $towns = Town::all();

        // 3. Create system admin users from the seed data.
        $this->createAdmins($counties, $towns);

        // 4. Create member users with random profiles and credit wallets.
        $this->createMembers($counties, $towns);

        // 5. Create escort users with profiles, rates, and metadata.
        $this->createEscorts($counties, $towns);

        // 6. Attach sample photos, reviews, and favourites for escorts.
        $this->createEscortSampleData();
    }

    /* ── Data loading ──────────────────────────────────────────────────── */

    /**
     * Loads every reference array from the combined JSON data file.
     * Each item in the array carries one non‑null field that identifies
     * which array it belongs to.
     */
    protected function loadReferenceData(): void
    {
        $path = database_path(self::DATA_FILE);

        Log::info('UserSeeder: loading reference data', ['path' => $path]);
        $this->command->info("  → Loading reference data from {$path}");

        $items = json_decode(file_get_contents($path), true);

        foreach ($items as $item) {
            if ($item['first_name']) {
                $this->firstNames[$item['gender']][] = $item['first_name'];
            }
            if ($item['last_name']) {
                $this->lastNames[] = $item['last_name'];
            }
            if ($item['escort_stage_name']) {
                $this->escortStageNames[] = $item['escort_stage_name'];
            }
            if ($item['special_features']) {
                $this->specialFeatures[] = $item['special_features'];
            }
            if ($item['languages']) {
                $this->languages[] = $item['languages'];
            }
            if ($item['bio']) {
                $this->bioTemplates[] = $item['bio'];
            }
            if ($item['review_comment']) {
                $this->reviewComments[] = $item['review_comment'];
            }
            if ($item['interests']) {
                $this->interests[] = $item['interests'];
            }
            if ($item['email']) {
                $this->seedAdmins[] = $item;
            }
        }

        Log::info('UserSeeder: reference data loaded', [
            'firstNames' => count($this->firstNames, COUNT_RECURSIVE) - 2,
            'lastNames' => count($this->lastNames),
            'stageNames' => count($this->escortStageNames),
            'features' => count($this->specialFeatures),
            'languages' => count($this->languages),
            'bios' => count($this->bioTemplates),
            'reviews' => count($this->reviewComments),
            'interests' => count($this->interests),
            'admins' => count($this->seedAdmins),
        ]);
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

    /* ── Admin seeding ─────────────────────────────────────────────────── */

    /**
     * Creates system‑admin users from the seed‑admin records in the
     * reference data. Each record specifies a county_name used to look
     * up the matching County + Town from the database.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $counties
     * @param  \Illuminate\Database\Eloquent\Collection  $towns
     */
    protected function createAdmins($counties, $towns): void
    {
        foreach ($this->seedAdmins as $adminData) {
            $email = $adminData['email'];

            if (User::where('email', $email)->exists()) {
                Log::info('UserSeeder: admin already exists, skipping', ['email' => $email]);
                $this->command->warn("  ↻ Admin {$email} already exists. Skipping...");

                continue;
            }

            $county = $counties->where('name', $adminData['county_name'])->first();
            $countyTowns = $towns->where('county_id', $county ? $county->id : null);

            $user = User::create([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'phone_number' => $adminData['phone_number'],
                'phone_verified' => true,
                'gender' => $adminData['gender'],
                'date_of_birth' => now()->subYears(30)->subMonths(rand(1, 12)),
                'location' => $adminData['location'],
                'county_id' => $county ? $county->id : null,
                'town_id' => $countyTowns->isNotEmpty() ? $countyTowns->first()->id : null,
                'latitude' => $adminData['latitude'],
                'longitude' => $adminData['longitude'],
                'user_type' => 'system_user',
                'status' => 'active',
                'meta_data' => json_encode([
                    'admin_level' => 'super',
                    'permissions' => ['all'],
                    'notes' => 'System administrator',
                ]),
            ]);

            // Ensure the 'admin' Spatie role exists before assigning it.
            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $user->assignRole('admin');

            Log::info('UserSeeder: created admin', ['email' => $email]);
            $this->command->info("  + Created admin → {$user->email}");
        }
    }

    /* ── Member seeding ────────────────────────────────────────────────── */

    /**
     * Creates MEMBER_COUNT member users with randomised personal details,
     * location data, and initial credit wallets.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $counties
     * @param  \Illuminate\Database\Eloquent\Collection  $towns
     */
    protected function createMembers($counties, $towns): void
    {
        $genders = ['male', 'female'];

        for ($i = 1; $i <= self::MEMBER_COUNT; $i++) {
            $email = "member{$i}@gmail.com";

            if (User::where('email', $email)->exists()) {
                $this->command->warn("  ↻ Member {$email} already exists. Skipping...");

                continue;
            }

            $gender = $genders[array_rand($genders)];
            $firstName = $this->firstNames[$gender][array_rand($this->firstNames[$gender])];
            $lastName = $this->lastNames[array_rand($this->lastNames)];
            $fullName = "{$firstName} {$lastName}";

            $randomCounty = $counties->random();
            $countyTowns = $towns->where('county_id', $randomCounty->id);
            $randomTown = $countyTowns->isNotEmpty() ? $countyTowns->random() : null;

            $dateOfBirth = now()->subYears(rand(25, 50))->subMonths(rand(1, 12));
            $initialCredits = rand(100, 1000);

            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make('password123'),
                'email_verified_at' => rand(0, 1) == 1 ? now()->subDays(rand(1, 30)) : null,
                'phone_number' => '+2547'.sprintf('%08d', 10000000 + $i),
                'phone_verified' => rand(0, 1) == 1,
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth,
                'location' => $randomTown ? $randomTown->name : $randomCounty->name,
                'county_id' => $randomCounty->id,
                'town_id' => $randomTown ? $randomTown->id : null,
                'latitude' => $this->generateKenyanLatitude(),
                'longitude' => $this->generateKenyanLongitude(),
                'user_type' => 'member',
                'status' => ['active', 'active', 'active', 'inactive'][rand(0, 3)],
                'meta_data' => json_encode([
                    'preferences' => [
                        'age_range' => [25, 45],
                        'interests' => $this->getRandomInterests(),
                        'notification_settings' => ['email' => true, 'sms' => rand(0, 1) == 1],
                    ],
                ]),
            ]);

            Member::create([
                'user_id' => $user->id,
                'credits' => $initialCredits,
                'total_credits_earned' => $initialCredits + rand(0, 500),
                'total_credits_spent' => rand(0, 300),
                'last_credit_purchase_at' => rand(0, 1) == 1 ? now()->subDays(rand(1, 60)) : null,
                'credits_expire_at' => rand(0, 1) == 1 ? now()->addDays(rand(30, 365)) : null,
            ]);

            Log::info('UserSeeder: created member', ['email' => $email, 'gender' => $gender]);
            $this->command->info("  + Created member → {$user->email}");
        }
    }

    /* ── Escort seeding ────────────────────────────────────────────────── */

    /**
     * Creates ESCORT_COUNT escort users with full profiles, rates, services,
     * and verification status. Approximately 15 female, 10 male, and 5 other.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $counties
     * @param  \Illuminate\Database\Eloquent\Collection  $towns
     */
    protected function createEscorts($counties, $towns): void
    {
        $bodyTypes = ['slim', 'athletic', 'average', 'curvy', 'muscular', 'stocky'];
        $hairColors = ['black', 'brown', 'blonde', 'red', 'gray', 'other'];
        $eyeColors = ['brown', 'blue', 'green', 'hazel', 'gray', 'other'];

        for ($i = 1; $i <= self::ESCORT_COUNT; $i++) {
            $email = "escort{$i}@gmail.com";

            if (User::where('email', $email)->exists()) {
                $this->command->warn("  ↻ Escort {$email} already exists. Skipping...");

                continue;
            }

            $gender = $i <= 15 ? 'female' : ($i <= 25 ? 'male' : 'other');
            $firstName = $gender == 'male'
                ? $this->firstNames['male'][array_rand($this->firstNames['male'])]
                : $this->firstNames['female'][array_rand($this->firstNames['female'])];
            $lastName = $this->lastNames[array_rand($this->lastNames)];
            $fullName = "{$firstName} {$lastName}";
            $stageName = $this->escortStageNames[array_rand($this->escortStageNames)].' '.
                $this->lastNames[array_rand($this->lastNames)];

            $randomCounty = $counties->random();
            $countyTowns = $towns->where('county_id', $randomCounty->id);
            $randomTown = $countyTowns->isNotEmpty() ? $countyTowns->random() : null;

            $dateOfBirth = now()->subYears(rand(21, 35))->subMonths(rand(1, 12));

            $services = $this->getRandomItems(getEscortServices(), 3, 6);
            $features = $this->getRandomItems($this->specialFeatures, 2, 4);
            $languagesArray = array_merge(
                ['English', 'Swahili'],
                $this->getRandomItems(array_diff($this->languages, ['English', 'Swahili']), 1, 2)
            );

            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make('password123'),
                'email_verified_at' => now()->subDays(rand(1, 30)),
                'phone_number' => '+2547'.sprintf('%08d', 20000000 + $i),
                'phone_verified' => true,
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth,
                'location' => $randomTown ? $randomTown->name : $randomCounty->name,
                'county_id' => $randomCounty->id,
                'town_id' => $randomTown ? $randomTown->id : null,
                'latitude' => $this->generateKenyanLatitude(),
                'longitude' => $this->generateKenyanLongitude(),
                'user_type' => 'escort',
                'status' => ['active', 'active', 'active', 'inactive'][rand(0, 3)],
                'meta_data' => json_encode([
                    'escort_info' => [
                        'experience_years' => rand(1, 10),
                        'specialization' => $services[0] ?? 'General Companion',
                        'availability_status' => ['available', 'busy', 'away'][rand(0, 2)],
                    ],
                ]),
            ]);

            Escort::create([
                'user_id' => $user->id,
                'stage_name' => $stageName,
                'bio' => $this->bioTemplates[array_rand($this->bioTemplates)],
                'available' => rand(0, 1) == 1,
                'working_hours' => $this->generateWorkingHours(),
                'height' => $gender == 'male' ? rand(170, 190) : rand(160, 180),
                'weight' => $gender == 'male' ? rand(65, 90) : rand(50, 75),
                'body_type' => $bodyTypes[array_rand($bodyTypes)],
                'hair_color' => $hairColors[array_rand($hairColors)],
                'eye_color' => $eyeColors[array_rand($eyeColors)],
                'services' => json_encode($services),
                'special_features' => json_encode($features),
                'languages' => json_encode($languagesArray),
                'rate_per_hour' => rand(5000, 20000),
                'rate_per_night' => rand(50000, 200000),
                'custom_rates' => json_encode([
                    'dinner_date' => rand(10000, 30000),
                    'weekend' => rand(150000, 500000),
                    'travel' => rand(20000, 50000),
                ]),
                'is_verified' => $i <= 20,
                'verification_status' => $i <= 20 ? 'verified' : ($i <= 25 ? 'pending' : 'rejected'),
                'view_count' => rand(100, 5000),
                'rating' => rand(35, 50) / 10,
                'review_count' => 0,
                'total_bookings' => rand(10, 200),
                'earnings' => rand(10000, 500000),
                'balance' => rand(1000, 50000),
                'featured' => $i <= 5,
                'accepting_new_clients' => rand(0, 1) == 1,
                'incall_available' => rand(0, 1) == 1,
                'outcall_available' => true,
                'travel_options' => json_encode([
                    'local' => true,
                    'national' => rand(0, 1) == 1,
                    'international' => $i <= 10,
                ]),
            ]);

            Log::info('UserSeeder: created escort', ['email' => $email, 'stage_name' => $stageName]);
            $this->command->info("  + Created escort → {$stageName} ({$user->email})");
        }
    }

    /* ── Sample data (photos, reviews, favourites) ─────────────────────── */

    /**
     * Attaches sample photos, reviews, and favourites to every escort.
     * Only the first 20 escorts receive reviews; the first 15 receive
     * favourites. Ratings are recomputed at the end.
     */
    protected function createEscortSampleData(): void
    {
        $escorts = Escort::all();

        foreach ($escorts as $escort) {
            $this->createEscortPhotos($escort);

            if ($escort->id <= 20) {
                $this->createEscortReviews($escort);
            }

            if ($escort->id <= 15) {
                $this->createEscortFavorites($escort);
            }
        }

        foreach ($escorts as $escort) {
            $escort->updateRating();
        }

        Log::info('UserSeeder: sample data created for escorts');
        $this->command->info('  ✓ Escort sample data created (photos, reviews, favourites).');
    }

    /**
     * Generates 3–8 random photos and (50 % chance) a video for an escort.
     */
    protected function createEscortPhotos(Escort $escort): void
    {
        $photoCount = rand(3, 8);

        for ($i = 1; $i <= $photoCount; $i++) {
            EscortResource::create([
                'escort_id' => $escort->id,
                'type' => 'photo',
                'path' => "escorts/{$escort->id}/photo{$i}.jpg",
                'thumbnail_path' => "escorts/{$escort->id}/thumb{$i}.jpg",
                'caption' => $i === 1 ? 'Profile Photo' : 'Gallery Photo '.$i,
                'is_primary' => $i === 1,
                'is_verified' => $escort->is_verified,
                'is_public' => true,
                'sort_order' => $i,
            ]);
        }

        // Optionally add a video introduction.
        if (rand(0, 1) == 1) {
            EscortResource::create([
                'escort_id' => $escort->id,
                'type' => 'video',
                'path' => "escorts/{$escort->id}/video1.mp4",
                'thumbnail_path' => "escorts/{$escort->id}/video-thumb1.jpg",
                'caption' => 'Introduction Video',
                'is_primary' => false,
                'is_verified' => $escort->is_verified,
                'is_public' => true,
                'sort_order' => $photoCount + 1,
            ]);
        }
    }

    /**
     * Generates 5–15 random reviews from distinct members for an escort.
     */
    protected function createEscortReviews(Escort $escort): void
    {
        $reviewCount = rand(5, 15);
        $members = User::where('user_type', 'member')->inRandomOrder()->take($reviewCount)->get();

        foreach ($members as $member) {
            if (Review::where('user_id', $member->id)->where('escort_id', $escort->id)->exists()) {
                continue;
            }

            Review::create([
                'user_id' => $member->id,
                'escort_id' => $escort->id,
                'rating' => rand(3, 5),
                'comment' => $this->reviewComments[array_rand($this->reviewComments)],
                'is_verified' => true,
                'is_visible' => true,
            ]);
        }
    }

    /**
     * Generates 3–10 random favourites from distinct members for an escort.
     */
    protected function createEscortFavorites(Escort $escort): void
    {
        $favoriteCount = rand(3, 10);
        $members = User::where('user_type', 'member')->inRandomOrder()->take($favoriteCount)->get();

        foreach ($members as $member) {
            if (Favorite::where('user_id', $member->id)->where('escort_id', $escort->id)->exists()) {
                continue;
            }

            Favorite::create([
                'user_id' => $member->id,
                'escort_id' => $escort->id,
            ]);
        }
    }

    /* ── Utility helpers ───────────────────────────────────────────────── */

    /**
     * Returns a random slice of $min–$max items from the given array.
     */
    private function getRandomItems(array $array, int $min, int $max): array
    {
        $count = rand($min, min($max, count($array)));
        shuffle($array);

        return array_slice($array, 0, $count);
    }

    /**
     * Returns 3–6 random interest labels from the loaded interests array.
     */
    private function getRandomInterests(): array
    {
        return $this->getRandomItems($this->interests, 3, 6);
    }

    /**
     * Generates a random latitude within Kenya's bounds (-4.0 to 4.0).
     */
    private function generateKenyanLatitude(): float
    {
        return rand(-40, 40) / 10.0;
    }

    /**
     * Generates a random longitude within Kenya's bounds (34.0 to 41.0).
     */
    private function generateKenyanLongitude(): float
    {
        return rand(340, 410) / 10.0;
    }

    /**
     * Generates a human-readable working-hours string
     * (e.g. "10:00 AM - 8:00 PM").
     */
    private function generateWorkingHours(): string
    {
        $startHour = rand(9, 12);
        $endHour = rand(18, 23);

        $ampmStart = $startHour < 12 ? 'AM' : 'PM';
        $ampmEnd = $endHour < 12 ? 'AM' : 'PM';

        $startHour = $startHour > 12 ? $startHour - 12 : $startHour;
        $endHour = $endHour > 12 ? $endHour - 12 : $endHour;

        return "{$startHour}:00 {$ampmStart} - {$endHour}:00 {$ampmEnd}";
    }
}

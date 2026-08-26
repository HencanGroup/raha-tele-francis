<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Escort;
use App\Models\EscortResource;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\Town;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * EscortSeeder
 * -----------------------------------------------------------------------------
 * Seeds escort profiles exclusively from database/data/escorts.json.
 *
 * Data source: database/data/escorts.json
 * Every record in the file is a full, distinct escort profile (identity,
 * location, physical, rates, availability, status). The seeder seeds those
 * records verbatim and soft-deletes any existing escort not present in the
 * file, so the JSON is the single source of truth for escorts.
 *
 * Idempotent — skips existing emails with a warning via existence checks,
 * so re‑running `php artisan db:seed` never duplicates records.
 *
 * Public entrypoint: run()
 * High‑level steps delegate to protected helpers below.
 */
class EscortSeeder extends Seeder
{
    /**
     * Relative path (from database/) to the escort data file.
     */
    protected const DATA_FILE = 'data/escorts.json';

    /**
     * Full escort records read verbatim from the data file.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $seedEscorts = [];

    /**
     * Collated review comments from the data file, used for sample reviews.
     *
     * @var array<int, string>
     */
    private array $reviewComments = [];

    /**
     * Orchestrates the seeding flow: load data file → ensure locations →
     * seed escorts verbatim → attach sample data.
     */
    public function run(): void
    {
        // 1. Load the full escort records from the JSON data file.
        $this->loadEscorts();

        // 2. Load location reference data (must exist for foreign keys).
        $this->ensureLocationsExist();

        $counties = County::all();
        $towns = Town::all();

        // 3. Seed escort users — every record from the JSON data file, verbatim.
        $this->createEscorts($counties, $towns);

        // 4. Attach sample photos, reviews, and favourites for escorts.
        $this->createEscortSampleData();
    }

    /* ── Data loading ──────────────────────────────────────────────────── */

    /**
     * Loads the full escort records and collated review comments from the
     * JSON data file, keeping each record with an email whole for seeding.
     */
    protected function loadEscorts(): void
    {
        $path = database_path(self::DATA_FILE);

        Log::info('EscortSeeder: loading escorts', ['path' => $path]);
        $this->command->info("  → Loading escorts from {$path}");

        $items = json_decode(file_get_contents($path), true);

        foreach ($items as $item) {
            if ($item['review_comment']) {
                $this->reviewComments[] = $item['review_comment'];
            }

            // Every record with an email is a complete escort profile.
            $this->seedEscorts[] = $item;
        }

        Log::info('EscortSeeder: escorts loaded', [
            'escorts' => count($this->seedEscorts),
            'reviewComments' => count($this->reviewComments),
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

    /* ── Escort seeding ────────────────────────────────────────────────── */

    /**
     * Seeds escort users exclusively from the JSON data file. Any existing
     * escort whose email is not present in the file is soft-deleted first,
     * so the database converges to the JSON as the single source of truth.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $counties
     * @param  \Illuminate\Database\Eloquent\Collection  $towns
     */
    protected function createEscorts($counties, $towns): void
    {
        // 1. Purge escorts not present in the JSON file (idempotent source-of-truth sync).
        $this->purgeNonJsonEscorts();

        // 2. Seed every full record from the JSON data file verbatim.
        foreach ($this->seedEscorts as $record) {
            $this->createFixedEscort($record, $counties, $towns);
        }
    }

    /**
     * Soft-deletes any escort user (including already-trashed ones) whose
     * email is not defined in the JSON data file, along with their linked
     * profile, keeping the escort roster exactly in sync with the file.
     * Users and Escort profiles use SoftDeletes, so purged escorts no
     * longer appear in listings or sample-data passes.
     */
    protected function purgeNonJsonEscorts(): void
    {
        $jsonEmails = array_column($this->seedEscorts, 'email');
        $staleEscorts = User::withTrashed()
            ->where('user_type', 'escort')
            ->whereNotIn('email', $jsonEmails)
            ->get();

        foreach ($staleEscorts as $escort) {
            Log::info('EscortSeeder: purging escort not in data file', ['email' => $escort->email]);
            $this->command->warn("  ✗ Purging escort → {$escort->email} (not in escorts.json)");
            $escort->escortProfile()->withTrashed()->forceDelete();
            $escort->forceDelete();
        }
    }

    /* ── Fixed escort seeding ──────────────────────────────────────────── */

    /**
     * Upserts one escort from a full JSON record. If the user already exists,
     * all fields are updated to reflect the latest values from the JSON file.
     *
     * @param  array<string, mixed>  $record
     * @param  \Illuminate\Database\Eloquent\Collection  $counties
     * @param  \Illuminate\Database\Eloquent\Collection  $towns
     */
    protected function createFixedEscort(array $record, $counties, $towns): void
    {
        $email = $record['email'];

        $county = $counties->where('name', $record['county_name'])->first();
        $countyTowns = $towns->where('county_id', $county ? $county->id : null);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $record['name'],
                'first_name' => $record['first_name'],
                'last_name' => $record['last_name'],
                'password' => Hash::make($record['password'] ?? 'password123'),
                'email_verified_at' => now(),
                'phone_number' => $record['phone_number'],
                'phone_verified' => true,
                'gender' => $record['gender'],
                'date_of_birth' => $record['date_of_birth'],
                'location' => $record['location'],
                'county_id' => $county ? $county->id : null,
                'town_id' => $countyTowns->isNotEmpty() ? $countyTowns->first()->id : null,
                'latitude' => $record['latitude'],
                'longitude' => $record['longitude'],
                'user_type' => 'escort',
                'status' => $record['status'],
                'meta_data' => [
                    'escort_info' => [
                        'experience_years' => rand(1, 10),
                        'specialization' => $record['services'][0] ?? 'General Companion',
                        'availability_status' => $record['available'] ? 'available' : 'away',
                    ],
                ],
            ]
        );

        Escort::updateOrCreate(
            ['user_id' => $user->id],
            [
                'stage_name' => $record['escort_stage_name'],
                'bio' => $record['bio'],
                'available' => $record['available'],
                'working_hours' => $record['working_hours'],
                'height' => $record['height'],
                'weight' => $record['weight'],
                'body_type' => $record['body_type'],
                'hair_color' => $record['hair_color'],
                'eye_color' => $record['eye_color'],
                'services' => $record['services'],
                'special_features' => $record['special_features'],
                'languages' => $record['languages'],
                'rate_per_hour' => $record['rate_per_hour'],
                'rate_per_night' => $record['rate_per_night'],
                'custom_rates' => $record['custom_rates'],
                'is_verified' => $record['is_verified'],
                'verification_status' => $record['verification_status'],
                'view_count' => rand(100, 5000),
                'rating' => rand(35, 50) / 10,
                'review_count' => 0,
                'total_bookings' => rand(10, 200),
                'earnings' => 0,
                'balance' => 0,
                'featured' => $record['featured'],
                'accepting_new_clients' => $record['accepting_new_clients'],
                'incall_available' => $record['incall_available'],
                'outcall_available' => $record['outcall_available'],
                'travel_options' => $record['travel_options'],
            ]
        );

        if ($user->wasRecentlyCreated) {
            Log::info('EscortSeeder: created escort', ['email' => $email, 'stage_name' => $record['escort_stage_name']]);
            $this->command->info("  + Created escort → {$record['escort_stage_name']} ({$user->email})");
        } else {
            Log::info('EscortSeeder: updated escort', ['email' => $email, 'stage_name' => $record['escort_stage_name']]);
            $this->command->info("  ↻ Updated escort → {$record['escort_stage_name']} ({$user->email})");
        }
    }

    /* ── Sample data (photos, reviews, favourites) ─────────────────────── */

    /**
     * Attaches sample photos, reviews, and favourites to every escort.
     * Ratings are recomputed at the end.
     */
    protected function createEscortSampleData(): void
    {
        $escorts = Escort::all();

        foreach ($escorts as $escort) {
            $this->createEscortPhotos($escort);
            $this->createEscortReviews($escort);
            $this->createEscortFavorites($escort);
        }

        foreach ($escorts as $escort) {
            $escort->updateRating();
        }

        Log::info('EscortSeeder: sample data created for escorts');
        $this->command->info('  ✓ Escort sample data created (photos, reviews, favourites).');
    }

    /**
     * Generates 3–8 random photos and (50 % chance) a video for an escort.
     * Uses updateOrCreate keyed on escort_id + type + sort_order so re-seeding
     * updates existing media without duplicating.
     */
    protected function createEscortPhotos(Escort $escort): void
    {
        $photoCount = rand(3, 8);

        for ($i = 1; $i <= $photoCount; $i++) {
            EscortResource::updateOrCreate(
                [
                    'escort_id' => $escort->id,
                    'type' => 'photo',
                    'sort_order' => $i,
                ],
                [
                    'path' => "escorts/{$escort->id}/photo{$i}.jpg",
                    'thumbnail_path' => "escorts/{$escort->id}/thumb{$i}.jpg",
                    'caption' => $i === 1 ? 'Profile Photo' : 'Gallery Photo '.$i,
                    'is_primary' => $i === 1,
                    'is_verified' => $escort->is_verified,
                    'is_public' => true,
                ]
            );
        }

        // Optionally add a video introduction.
        if (rand(0, 1) == 1) {
            EscortResource::updateOrCreate(
                [
                    'escort_id' => $escort->id,
                    'type' => 'video',
                    'sort_order' => $photoCount + 1,
                ],
                [
                    'path' => "escorts/{$escort->id}/video1.mp4",
                    'thumbnail_path' => "escorts/{$escort->id}/video-thumb1.jpg",
                    'caption' => 'Introduction Video',
                    'is_primary' => false,
                    'is_verified' => $escort->is_verified,
                    'is_public' => true,
                ]
            );
        }
    }

    /**
     * Generates 5–15 random reviews from distinct members for an escort.
     * Uses updateOrCreate keyed on user_id + escort_id so re-seeding
     * updates existing reviews without duplicating.
     */
    protected function createEscortReviews(Escort $escort): void
    {
        $reviewCount = rand(5, 15);
        $members = User::where('user_type', 'member')->inRandomOrder()->take($reviewCount)->get();

        foreach ($members as $member) {
            Review::updateOrCreate(
                [
                    'user_id' => $member->id,
                    'escort_id' => $escort->id,
                ],
                [
                    'rating' => rand(3, 5),
                    'comment' => $this->reviewComments[array_rand($this->reviewComments)],
                    'is_verified' => true,
                    'is_visible' => true,
                ]
            );
        }
    }

    /**
     * Generates 3–10 random favourites from distinct members for an escort.
     * Uses updateOrCreate keyed on user_id + escort_id so re-seeding
     * is safe.
     */
    protected function createEscortFavorites(Escort $escort): void
    {
        $favoriteCount = rand(3, 10);
        $members = User::where('user_type', 'member')->inRandomOrder()->take($favoriteCount)->get();

        foreach ($members as $member) {
            Favorite::updateOrCreate(
                [
                    'user_id' => $member->id,
                    'escort_id' => $escort->id,
                ],
                []
            );
        }
    }
}

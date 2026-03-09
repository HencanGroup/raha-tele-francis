<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Escort;
use App\Models\County;
use App\Models\Town;
use App\Models\EscortResource;
use App\Models\Review;
use App\Models\Favorite;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    private $firstNames = [
        'male' => [
            'John',
            'James',
            'David',
            'Michael',
            'Robert',
            'William',
            'Joseph',
            'Charles',
            'Thomas',
            'Daniel'
        ],
        'female' => [
            'Mary',
            'Patricia',
            'Jennifer',
            'Linda',
            'Elizabeth',
            'Barbara',
            'Susan',
            'Jessica',
            'Sarah',
            'Karen'
        ],
    ];

    private $lastNames = [
        'Smith',
        'Johnson',
        'Williams',
        'Brown',
        'Jones',
        'Garcia',
        'Miller',
        'Davis',
        'Rodriguez',
        'Martinez',
        'Kamau',
        'Ochieng',
        'Mwangi',
        'Kipchoge',
        'Otieno',
        'Ndungu',
        'Wanjiru',
        'Nyambura',
        'Maina',
        'Kariuki',
        'Mutua',
        'Ndegwa',
        'Waweru',
        'Kinyua',
        'Muthoni',
        'Njoroge',
        'Gitau',
        'Omondi',
        'Akinyi',
        'Adhiambo'
    ];

    private $escortStageNames = [
        'Crystal',
        'Diamond',
        'Ruby',
        'Sapphire',
        'Amber',
        'Jade',
        'Pearl',
        'Ivory',
        'Velvet',
        'Silk',
        'Bella',
        'Luna',
        'Stella',
        'Aria',
        'Mia',
        'Ava',
        'Zoe',
        'Chloe',
        'Lily',
        'Rose',
        'Hunter',
        'Blaze',
        'Jax',
        'Ryder',
        'Phoenix',
        'Storm',
        'Wolf',
        'Fox',
        'Hawk',
        'Raven'
    ];

    private $specialFeatures = [
        'Multilingual',
        'Great Conversationalist',
        'Model Material',
        'Well Educated',
        'World Traveler',
        'VIP Experience',
        'Discreet Service',
        'Luxury Companion',
        'High Class',
        'Executive Level'
    ];

    private $languages = [
        'English',
        'Swahili',
        'French',
        'Spanish',
        'German',
        'Italian',
        'Chinese',
        'Arabic',
        'Russian',
        'Japanese'
    ];

    private $bioTemplates = [
        "Professional companion with years of experience. Discreet, reliable, and dedicated to providing exceptional companionship for all occasions.",
        "A sophisticated and elegant companion offering discreet services for discerning clients. Experienced in social events, travel, and meaningful connections.",
        "Professional escort specializing in luxury companionship, dinner dates, and travel companionship.",
        "Offers high-class companionship services for business professionals and discerning clients. Discreet, professional, and focused on your satisfaction.",
        "Provides premium escort services including social events, travel companionship, and exclusive dates. Experience, discretion, and professionalism guaranteed.",
        "Elite companion with a passion for making every encounter memorable. Professional, discreet, and attentive to every detail.",
        "Experienced in providing quality companionship for business events, social gatherings, and private occasions.",
        "Specializes in creating comfortable, enjoyable experiences for clients seeking quality companionship.",
        "Professional with a warm personality, dedicated to ensuring your time together is exceptional.",
        "Offers discreet and professional companionship services tailored to your specific needs and preferences."
    ];

    private $reviewComments = [
        "Amazing experience, very professional and discreet.",
        "Great companion for business events, highly recommended.",
        "Made my evening unforgettable, will definitely book again.",
        "Professional, punctual, and excellent company.",
        "Exceeded all expectations, a true professional.",
        "Perfect companion for my business trip, very reliable.",
        "Beautiful personality and great conversation.",
        "Discreet and professional service, highly satisfied.",
        "Went above and beyond to make the experience special.",
        "Highly recommended for anyone seeking quality companionship."
    ];

    public function run(): void
    {
        // Get existing counties and towns from database
        $counties = County::all();
        $towns = Town::all();

        // Check if we have counties
        if ($counties->isEmpty()) {
            $this->command->error('No counties found in database. Please run counties seeder first.');
            return;
        }

        if ($towns->isEmpty()) {
            $this->command->error('No towns found in database. Please run towns seeder first.');
            return;
        }

        $this->command->info("Found {$counties->count()} counties and {$towns->count()} towns in database.");

        // Create Admins (2)
        $this->createAdmins($counties, $towns);

        // Create Members (10)
        $this->createMembers($counties, $towns);

        // Create Escorts (30)
        $this->createEscorts($counties, $towns);

        // Create sample data for escorts (photos, reviews, favorites)
        $this->createEscortSampleData();
    }

    private function createAdmins($counties, $towns): void
    {
        $admins = [
            [
                'name' => 'Admin Master',
                'email' => 'admin@escortapp.com',
                'phone_number' => '+254700000001',
                'gender' => 'male',
                'location' => 'Nairobi CBD'
            ],
            [
                'name' => 'System Admin',
                'email' => 'system@escortapp.com',
                'phone_number' => '+254700000002',
                'gender' => 'female',
                'location' => 'Mombasa CBD'
            ]
        ];

        foreach ($admins as $index => $adminData) {
            if (User::where('email', $adminData['email'])->exists()) {
                $this->command->warn("Admin {$adminData['email']} already exists. Skipping...");
                continue;
            }

            // Get appropriate county and town
            $county = $counties->where('name', $index === 0 ? 'Nairobi' : 'Mombasa')->first();
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
                'latitude' => $index === 0 ? -1.286389 : -4.043477,
                'longitude' => $index === 0 ? 36.817223 : 39.668206,
                'credits' => 1000.00,
                'total_credits_earned' => 1000.00,
                'total_credits_spent' => 0.00,
                'last_credit_purchase_at' => now()->subDays(30),
                'credits_expire_at' => now()->addYear(),
                'status' => 'active',
                'meta_data' => json_encode([
                    'admin_level' => 'super',
                    'permissions' => ['all'],
                    'notes' => 'System administrator'
                ]),
            ]);

            $user->assignRole('admin');

            $this->command->info("Created admin: {$user->email}");
        }
    }

    private function createMembers($counties, $towns): void
    {
        $genders = ['male', 'female'];

        for ($i = 1; $i <= 10; $i++) {
            $email = "member{$i}@gmail.com";

            if (User::where('email', $email)->exists()) {
                $this->command->warn("Member {$email} already exists. Skipping...");
                continue;
            }

            $gender = $genders[array_rand($genders)];
            $firstName = $this->firstNames[$gender][array_rand($this->firstNames[$gender])];
            $lastName = $this->lastNames[array_rand($this->lastNames)];
            $fullName = "{$firstName} {$lastName}";

            // Get random county and town
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
                'phone_number' => '+2547' . sprintf('%08d', 10000000 + $i),
                'phone_verified' => rand(0, 1) == 1,
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth,
                'location' => $randomTown ? $randomTown->name : $randomCounty->name,
                'county_id' => $randomCounty->id,
                'town_id' => $randomTown ? $randomTown->id : null,
                'latitude' => $this->generateKenyanLatitude(),
                'longitude' => $this->generateKenyanLongitude(),
                'credits' => $initialCredits,
                'total_credits_earned' => $initialCredits + rand(0, 500),
                'total_credits_spent' => rand(0, 300),
                'last_credit_purchase_at' => rand(0, 1) == 1 ? now()->subDays(rand(1, 60)) : null,
                'credits_expire_at' => rand(0, 1) == 1 ? now()->addDays(rand(30, 365)) : null,
                'status' => ['active', 'active', 'active', 'inactive'][rand(0, 3)],
                'meta_data' => json_encode([
                    'preferences' => [
                        'age_range' => [25, 45],
                        'interests' => $this->getRandomInterests(),
                        'notification_settings' => ['email' => true, 'sms' => rand(0, 1) == 1]
                    ]
                ]),
            ]);

            $user->assignRole('member');

            $this->command->info("Created member: {$user->email}");
        }
    }

    private function createEscorts($counties, $towns): void
    {
        $bodyTypes = ['slim', 'athletic', 'average', 'curvy', 'muscular', 'stocky'];
        $hairColors = ['black', 'brown', 'blonde', 'red', 'gray', 'other'];
        $eyeColors = ['brown', 'blue', 'green', 'hazel', 'gray', 'other'];

        for ($i = 1; $i <= 30; $i++) {
            $email = "escort{$i}@gmail.com";

            if (User::where('email', $email)->exists()) {
                $this->command->warn("Escort {$email} already exists. Skipping...");
                continue;
            }

            $gender = $i <= 15 ? 'female' : ($i <= 25 ? 'male' : 'other');
            $firstName = $gender == 'male'
                ? $this->firstNames['male'][array_rand($this->firstNames['male'])]
                : $this->firstNames['female'][array_rand($this->firstNames['female'])];
            $lastName = $this->lastNames[array_rand($this->lastNames)];
            $fullName = "{$firstName} {$lastName}";
            $stageName = $this->escortStageNames[array_rand($this->escortStageNames)] . ' ' .
                $this->lastNames[array_rand($this->lastNames)];

            // Get random county and town
            $randomCounty = $counties->random();
            $countyTowns = $towns->where('county_id', $randomCounty->id);
            $randomTown = $countyTowns->isNotEmpty() ? $countyTowns->random() : null;

            $dateOfBirth = now()->subYears(rand(21, 35))->subMonths(rand(1, 12));

            // Generate random services
            $services = $this->getRandomItems(getEscortServices(), 3, 6);
            $features = $this->getRandomItems($this->specialFeatures, 2, 4);
            $languagesArray = array_merge(['English', 'Swahili'], $this->getRandomItems(array_diff($this->languages, ['English', 'Swahili']), 1, 2));

            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make('password123'),
                'email_verified_at' => now()->subDays(rand(1, 30)),
                'phone_number' => '+2547' . sprintf('%08d', 20000000 + $i),
                'phone_verified' => true,
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth,
                'location' => $randomTown ? $randomTown->name : $randomCounty->name,
                'county_id' => $randomCounty->id,
                'town_id' => $randomTown ? $randomTown->id : null,
                'latitude' => $this->generateKenyanLatitude(),
                'longitude' => $this->generateKenyanLongitude(),
                'credits' => rand(0, 200),
                'total_credits_earned' => rand(100, 2000),
                'total_credits_spent' => rand(50, 1000),
                'last_credit_purchase_at' => now()->subDays(rand(1, 60)),
                'credits_expire_at' => now()->addDays(rand(30, 365)),
                'status' => ['active', 'active', 'active', 'inactive'][rand(0, 3)],
                'meta_data' => json_encode([
                    'escort_info' => [
                        'experience_years' => rand(1, 10),
                        'specialization' => $services[0] ?? 'General Companion',
                        'availability_status' => ['available', 'busy', 'away'][rand(0, 2)]
                    ]
                ]),
            ]);

            $user->assignRole('escort');

            // Create escort profile
            $escort = Escort::create([
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
                'review_count' => 0, // Will be updated when reviews are created
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

            $this->command->info("Created escort: {$stageName} ({$user->email})");
        }
    }

    private function createEscortSampleData(): void
    {
        $escorts = Escort::all();

        foreach ($escorts as $escort) {
            // Create sample photos
            $this->createEscortPhotos($escort);

            // Create reviews for some escorts
            if ($escort->id <= 20) { // Only create reviews for first 20 escorts
                $this->createEscortReviews($escort);
            }

            // Create favorites for some escorts
            if ($escort->id <= 15) {
                $this->createEscortFavorites($escort);
            }
        }

        // Update escort ratings based on reviews
        foreach ($escorts as $escort) {
            $escort->updateRating();
        }
    }

    private function createEscortPhotos(Escort $escort): void
    {
        $photoCount = rand(3, 8);

        for ($i = 1; $i <= $photoCount; $i++) {
            EscortResource::create([
                'escort_id' => $escort->id,
                'type' => 'photo',
                'path' => "escorts/{$escort->id}/photo{$i}.jpg",
                'thumbnail_path' => "escorts/{$escort->id}/thumb{$i}.jpg",
                'caption' => $i === 1 ? 'Profile Photo' : 'Gallery Photo ' . $i,
                'is_primary' => $i === 1,
                'is_verified' => $escort->is_verified,
                'is_public' => true,
                'sort_order' => $i
            ]);
        }

        // Maybe add a video for some escorts
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
                'sort_order' => $photoCount + 1
            ]);
        }
    }

    private function createEscortReviews(Escort $escort): void
    {
        $reviewCount = rand(5, 15);
        $members = User::whereHas('roles', function ($query) {
            $query->where('name', 'member');
        })->inRandomOrder()->take($reviewCount)->get();

        foreach ($members as $member) {
            // Check if this member already reviewed this escort
            if (Review::where('user_id', $member->id)->where('escort_id', $escort->id)->exists()) {
                continue;
            }

            Review::create([
                'user_id' => $member->id,
                'escort_id' => $escort->id,
                'rating' => rand(3, 5),
                'comment' => $this->reviewComments[array_rand($this->reviewComments)],
                'is_verified' => true,
                'is_visible' => true
            ]);
        }
    }

    private function createEscortFavorites(Escort $escort): void
    {
        $favoriteCount = rand(3, 10);
        $members = User::whereHas('roles', function ($query) {
            $query->where('name', 'member');
        })->inRandomOrder()->take($favoriteCount)->get();

        foreach ($members as $member) {
            // Check if this member already favorited this escort
            if (Favorite::where('user_id', $member->id)->where('escort_id', $escort->id)->exists()) {
                continue;
            }

            Favorite::create([
                'user_id' => $member->id,
                'escort_id' => $escort->id
            ]);
        }
    }

    private function getRandomItems(array $array, int $min, int $max): array
    {
        $count = rand($min, min($max, count($array)));
        shuffle($array);
        return array_slice($array, 0, $count);
    }

    private function getRandomInterests(): array
    {
        $allInterests = ['Dining', 'Movies', 'Travel', 'Music', 'Sports', 'Art', 'Theater', 'Wine', 'Adventure', 'Reading'];
        return $this->getRandomItems($allInterests, 3, 6);
    }

    private function generateKenyanLatitude(): float
    {
        // Kenya latitude ranges from about -4.0 to 4.0
        return rand(-40, 40) / 10.0;
    }

    private function generateKenyanLongitude(): float
    {
        // Kenya longitude ranges from about 34.0 to 41.0
        return rand(340, 410) / 10.0;
    }

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
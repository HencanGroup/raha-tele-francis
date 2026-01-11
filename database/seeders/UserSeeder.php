<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Escort;
use App\Models\County;
use App\Models\Town;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    private $firstNames = [
        'male' => ['John', 'James', 'David', 'Michael', 'Robert', 'William', 'Joseph', 'Charles', 'Thomas', 'Daniel'],
        'female' => ['Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica', 'Sarah', 'Karen'],
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

    private $escortServices = [
        'Dinner Dates',
        'Social Events',
        'Travel Companion',
        'Weekend Getaways',
        'Business Meetings',
        'Massage Therapy',
        'Role Play',
        'Fantasy Fulfillment',
        'Sensual Companionship',
        'Therapeutic Listening'
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

    public function run(): void
    {
        // Get existing counties and towns from database
        $counties = County::all()->keyBy('name');
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

        // Create 2 Admins
        $this->createAdmins($counties, $towns);

        // Create 10 Members
        $this->createMembers($counties, $towns);

        // Create 30 Escorts
        $this->createEscorts($counties, $towns);
    }

    private function createAdmins($counties, $towns): void
    {
        $admins = [
            [
                'name' => 'Admin Master',
                'email' => 'admin@escortapp.com',
                'phone_number' => '+254700000001',
                'gender' => 'male'
            ],
            [
                'name' => 'System Admin',
                'email' => 'system@escortapp.com',
                'phone_number' => '+254700000002',
                'gender' => 'female'
            ]
        ];

        foreach ($admins as $adminData) {
            // Check if admin already exists
            if (User::where('email', $adminData['email'])->exists()) {
                $this->command->warn("Admin {$adminData['email']} already exists. Skipping...");
                continue;
            }

            $user = User::create([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => Hash::make('admin123'),
                'phone_number' => $adminData['phone_number'],
                'phone_verified' => true,
                'credits' => 1000.00,
                'total_credits_earned' => 1000.00,
                'total_credits_spent' => 0.00,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            // Assign admin role
            $user->assignRole('admin');

            // Get Nairobi county and its towns
            $nairobiCounty = $counties->get('Nairobi');
            $nairobiTowns = $towns->where('county_id', $nairobiCounty ? $nairobiCounty->id : null);

            // Create admin profile
            UserProfile::create([
                'user_id' => $user->id,
                'gender' => $adminData['gender'],
                'bio' => 'System Administrator with full access to all features.',
                'county_id' => $nairobiCounty ? $nairobiCounty->id : null,
                'town_id' => $nairobiTowns->isNotEmpty() ? $nairobiTowns->first()->id : null,
                'location' => 'Nairobi CBD',
                'latitude' => -1.286389,
                'longitude' => 36.817223,
            ]);

            $this->command->info("Created admin: {$user->email}");
        }
    }

    private function createMembers($counties, $towns): void
    {
        $genders = ['male', 'female'];

        for ($i = 1; $i <= 10; $i++) {
            $email = "member{$i}@example.com";

            // Check if member already exists
            if (User::where('email', $email)->exists()) {
                $this->command->warn("Member {$email} already exists. Skipping...");
                continue;
            }

            $gender = $genders[array_rand($genders)];
            $firstName = $this->firstNames[$gender][array_rand($this->firstNames[$gender])];
            $lastName = $this->lastNames[array_rand($this->lastNames)];

            $user = User::create([
                'name' => "{$firstName} {$lastName}",
                'email' => $email,
                'password' => Hash::make('password123'),
                'phone_number' => '+2547' . sprintf('%08d', 10000000 + $i),
                'phone_verified' => rand(0, 1) == 1,
                'credits' => rand(0, 500),
                'total_credits_earned' => rand(0, 1000),
                'total_credits_spent' => rand(0, 500),
                'status' => ['active', 'active', 'active', 'inactive'][rand(0, 3)],
                'email_verified_at' => rand(0, 1) == 1 ? now()->subDays(rand(1, 30)) : null,
            ]);

            // Assign member role
            $user->assignRole('member');

            // Get random county and its towns
            $randomCounty = $counties->random();
            $countyTowns = $towns->where('county_id', $randomCounty->id);

            $preferences = [
                'age_range' => [25, 45],
                'interests' => $this->getRandomInterests(),
                'notification_settings' => ['email' => true, 'sms' => rand(0, 1) == 1]
            ];

            UserProfile::create([
                'user_id' => $user->id,
                'gender' => $gender,
                'searching_for' => ['male', 'female', 'both'][rand(0, 2)],
                'birth_date' => now()->subYears(rand(25, 50))->subMonths(rand(1, 12)),
                'age' => rand(25, 50),
                'bio' => $this->generateMemberBio($gender),
                'profile_picture' => null,
                'gallery' => null,
                'county_id' => $randomCounty->id,
                'town_id' => $countyTowns->isNotEmpty() ? $countyTowns->random()->id : null,
                'location' => "{$randomCounty->name} Area",
                'latitude' => $this->generateKenyanLatitude(),
                'longitude' => $this->generateKenyanLongitude(),
                'preferences' => json_encode($preferences),
            ]);

            $this->command->info("Created member: {$user->email}");
        }
    }

    private function createEscorts($counties, $towns): void
    {
        $escortGenders = ['male', 'female', 'transgender'];
        $bodyTypes = ['slim', 'athletic', 'average', 'curvy', 'muscular', 'stocky'];
        $hairColors = ['black', 'brown', 'blonde', 'red', 'gray', 'other'];
        $eyeColors = ['brown', 'blue', 'green', 'hazel', 'gray', 'other'];

        for ($i = 1; $i <= 30; $i++) {
            $email = "escort{$i}@example.com";

            // Check if escort already exists
            if (User::where('email', $email)->exists()) {
                $this->command->warn("Escort {$email} already exists. Skipping...");
                continue;
            }

            $gender = $escortGenders[array_rand($escortGenders)];
            $firstName = $gender == 'male'
                ? $this->firstNames['male'][array_rand($this->firstNames['male'])]
                : $this->firstNames['female'][array_rand($this->firstNames['female'])];
            $lastName = $this->lastNames[array_rand($this->lastNames)];
            $stageName = $this->escortStageNames[array_rand($this->escortStageNames)] . ' ' .
                $this->lastNames[array_rand($this->lastNames)];

            $user = User::create([
                'name' => "{$firstName} {$lastName}",
                'email' => $email,
                'password' => Hash::make('password123'),
                'phone_number' => '+2547' . sprintf('%08d', 20000000 + $i),
                'phone_verified' => true,
                'credits' => rand(0, 200),
                'total_credits_earned' => rand(100, 2000),
                'total_credits_spent' => rand(50, 1000),
                'last_credit_purchase_at' => now()->subDays(rand(1, 60)),
                'credits_expire_at' => now()->addDays(rand(30, 365)),
                'status' => ['active', 'active', 'active', 'inactive'][rand(0, 3)],
                'email_verified_at' => now()->subDays(rand(1, 30)),
            ]);

            // Assign escort role
            $user->assignRole('escort');

            // Get random county and its towns
            $randomCounty = $counties->random();
            $countyTowns = $towns->where('county_id', $randomCounty->id);

            // Generate random services
            $services = [];
            $numServices = rand(3, 6);
            $availableServices = $this->escortServices;
            shuffle($availableServices);
            for ($j = 0; $j < $numServices && $j < count($availableServices); $j++) {
                $services[] = $availableServices[$j];
            }

            // Generate special features
            $features = [];
            $numFeatures = rand(2, 4);
            $availableFeatures = $this->specialFeatures;
            shuffle($availableFeatures);
            for ($j = 0; $j < $numFeatures && $j < count($availableFeatures); $j++) {
                $features[] = $availableFeatures[$j];
            }

            // Generate languages
            $languagesArray = ['English', 'Swahili'];
            $numExtraLanguages = rand(1, 2);
            $availableLanguages = array_diff($this->languages, $languagesArray);
            shuffle($availableLanguages);
            for ($j = 0; $j < $numExtraLanguages && $j < count($availableLanguages); $j++) {
                $languagesArray[] = $availableLanguages[$j];
            }

            // Create escort profile
            Escort::create([
                'user_id' => $user->id,
                'stage_name' => $stageName,
                'gender' => $gender,
                'birth_date' => now()->subYears(rand(21, 35))->subMonths(rand(1, 12)),
                'age' => rand(21, 35),
                'bio' => $this->generateEscortBio($stageName, $i),
                'profile_picture' => null,
                'county_id' => $randomCounty->id,
                'town_id' => $countyTowns->isNotEmpty() ? $countyTowns->random()->id : null,
                'location' => "{$randomCounty->name} CBD",
                'latitude' => $this->generateKenyanLatitude(),
                'longitude' => $this->generateKenyanLongitude(),
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
                'rate_per_hour' => rand(5000, 20000), // 5,000 - 20,000 KES
                'rate_per_night' => rand(50000, 200000), // 50,000 - 200,000 KES
                'custom_rates' => json_encode([
                    'dinner_date' => rand(10000, 30000),
                    'weekend' => rand(150000, 500000),
                    'travel' => rand(20000, 50000),
                ]),
                'is_verified' => $i <= 20, // First 20 are verified
                'verification_status' => $i <= 20 ? 'verified' : ($i <= 25 ? 'pending' : 'rejected'),
                'view_count' => rand(100, 5000),
                'rating' => rand(35, 50) / 10, // 3.5 - 5.0
                'review_count' => rand(5, 100),
                'total_bookings' => rand(10, 200),
                'earnings' => rand(10000, 500000),
                'balance' => rand(1000, 50000),
                'featured' => $i <= 5, // First 5 are featured
                'accepting_new_clients' => rand(0, 1) == 1,
                'incall_available' => rand(0, 1) == 1,
                'outcall_available' => true,
                'travel_options' => json_encode([
                    'local' => true,
                    'national' => rand(0, 1) == 1,
                    'international' => $i <= 10, // First 10 offer international
                ]),
            ]);

            $this->command->info("Created escort: {$stageName} ({$user->email})");
        }
    }

    /**
     * Helper method to generate Kenyan latitude
     */
    private function generateKenyanLatitude(): float
    {
        // Kenya latitude ranges from about -4.0 to 4.0
        return rand(-40, 40) / 10.0;
    }

    /**
     * Helper method to generate Kenyan longitude
     */
    private function generateKenyanLongitude(): float
    {
        // Kenya longitude ranges from about 34.0 to 41.0
        return rand(340, 410) / 10.0;
    }

    /**
     * Helper method to generate working hours
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

    /**
     * Helper method to generate member bio
     */
    private function generateMemberBio(string $gender): string
    {
        $bios = [
            "I'm a professional looking for meaningful connections and companionship in my free time.",
            "As a busy executive, I appreciate quality time with interesting and engaging people.",
            "New to the area and looking to meet new people for social activities and conversations.",
            "Enjoy fine dining, cultural events, and intelligent conversations with interesting individuals.",
            "Looking for discreet companionship for social events and occasional outings.",
            "Professional with a busy schedule seeking quality companionship for leisure activities.",
            "Enjoy traveling, good food, and stimulating conversations with like-minded people.",
            "Seeking genuine connections and memorable experiences with interesting personalities.",
            "Business owner looking for occasional companionship for events and social gatherings.",
            "Relocated recently and looking to expand my social circle with quality connections."
        ];

        return $bios[array_rand($bios)];
    }

    /**
     * Helper method to generate escort bio
     */
    private function generateEscortBio(string $stageName, int $experienceYears): string
    {
        $templates = [
            "Professional companion {$stageName} with {$experienceYears} years of experience. Discreet, reliable, and dedicated to providing exceptional companionship for all occasions.",
            "{$stageName} - A sophisticated and elegant companion offering discreet services for discerning clients. Experienced in social events, travel, and meaningful connections.",
            "Meet {$stageName}, a professional escort with {$experienceYears}+ years in the industry. Specializing in luxury companionship, dinner dates, and travel companionship.",
            "{$stageName} offers high-class companionship services for business professionals and discerning clients. Discreet, professional, and focused on your satisfaction.",
            "As {$stageName}, I provide premium escort services including social events, travel companionship, and exclusive dates. Experience, discretion, and professionalism guaranteed."
        ];

        return $templates[array_rand($templates)];
    }

    /**
     * Helper method to get random interests
     */
    private function getRandomInterests(): array
    {
        $allInterests = ['Dining', 'Movies', 'Travel', 'Music', 'Sports', 'Art', 'Theater', 'Wine', 'Adventure', 'Reading'];
        $numInterests = rand(3, 6);
        shuffle($allInterests);
        return array_slice($allInterests, 0, $numInterests);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Essential features to get started',
                'price' => 200.00,
                'billing_period' => 7, // 7 days
                'features' => json_encode([
                    'Profile listing',
                    'Basic search visibility',
                    '5 photos in gallery',
                    'Standard customer support',
                    '10 messages per day',
                    'Basic analytics',
                ]),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Enhanced visibility and features',
                'price' => 600.00,
                'billing_period' => 30, // 30 days
                'features' => json_encode([
                    'All Basic features',
                    'Priority listing in search',
                    '20 photos in gallery',
                    'Video uploads',
                    'Unlimited messages',
                    'Advanced analytics',
                    'Verified badge',
                    'Priority customer support',
                    'Featured in recommended section',
                ]),
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'VIP',
                'slug' => 'vip',
                'description' => 'Maximum exposure',
                'price' => 2400.00,
                'billing_period' => 180, // 180 days
                'features' => json_encode([
                    'All Premium features',
                    'Top placement in all searches',
                    'Unlimited photos/videos',
                    'VIP badge and highlighting',
                    'Dedicated account manager',
                    '24/7 priority support',
                    'Featured on homepage',
                    'Promoted in newsletters',
                    'Advanced marketing tools',
                    'Weekly performance reports',
                ]),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert plans
        DB::table('plans')->insert($plans);

        // Add plan features to the plan_features table
        $this->createPlanFeatures();
    }

    protected function createPlanFeatures()
    {
        $features = [
            // Basic Plan Features
            [
                'plan_id' => 1,
                'name' => 'Photo Uploads',
                'value' => '5',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 1,
                'name' => 'Daily Messages',
                'value' => '10',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 1,
                'name' => 'Search Visibility',
                'value' => 'Standard',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Premium Plan Features
            [
                'plan_id' => 2,
                'name' => 'Photo Uploads',
                'value' => '20',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 2,
                'name' => 'Video Uploads',
                'value' => '5',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 2,
                'name' => 'Verification Badge',
                'value' => 'Yes',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 2,
                'name' => 'Search Visibility',
                'value' => 'Priority',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // VIP Plan Features
            [
                'plan_id' => 3,
                'name' => 'Photo Uploads',
                'value' => 'Unlimited',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 3,
                'name' => 'Video Uploads',
                'value' => 'Unlimited',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 3,
                'name' => 'Homepage Featured',
                'value' => 'Yes',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 3,
                'name' => 'Dedicated Support',
                'value' => '24/7',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 3,
                'name' => 'Search Visibility',
                'value' => 'Top Placement',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('plan_features')->insert($features);
    }
}

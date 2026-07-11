<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Aggrey',
                'last_name' => 'Yorris',
                'name' => 'Aggrey Yorris',
                'email' => 'aggreyyorris@gmail.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'phone_number' => '+254700000001',
                'phone_verified' => true,
                'user_type' => 'system_user',
                'status' => 'active',
                'is_temp_password' => false,
                'role' => 'super_admin',
            ],
        ];

        foreach ($users as $userData) {
            $email = $userData['email'];

            if (User::where('email', $email)->exists()) {
                $this->command->warn("User {$email} already exists. Updating role...");
                $user = User::where('email', $email)->first();
            } else {
                $role = $userData['role'];
                unset($userData['role']);

                $user = User::create($userData);
                $this->command->info("Created user: {$email}");
            }

            $user->assignRole($role ?? 'super_admin');
        }
    }
}

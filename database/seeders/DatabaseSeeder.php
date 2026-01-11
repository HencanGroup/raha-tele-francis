<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call other seeders
        $this->call([
            KenyaCountiesSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class
        ]);
    }
}

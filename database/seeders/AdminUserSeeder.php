<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * AdminUserSeeder
 * -----------------------------------------------------------------------------
 * Seeds the super‑admin user(s) from a JSON definition file.
 *
 * Data source: database/data/admin-users.json
 * Each record contains user fields plus a 'role' key that is assigned after
 * the User row is created.
 *
 * Idempotent — skips existing emails with a warning, assigns the role to the
 * existing record instead of failing.
 *
 * Public entrypoint: run()
 * All business logic is delegated to the protected helpers below so the
 * run() method reads as a high‑level orchestration script.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Relative path (from database/) to the admin‑user data file.
     */
    protected const DATA_FILE = 'data/admin-users.json';

    /**
     * Orchestrates the seeding flow: load data → upsert user → assign role.
     */
    public function run(): void
    {
        // 1. Load the admin‑user data from the JSON file.
        $users = $this->loadAdminUsers();

        foreach ($users as $userData) {
            // 2. Resolve or create the User record.
            $user = $this->upsertAdminUser($userData);

            // 3. Assign the configured Spatie role.
            $this->assignRole($user, $userData['role'] ?? 'super_admin');
        }
    }

    /**
     * Loads and returns the admin‑user array from the JSON data file.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function loadAdminUsers(): array
    {
        $path = database_path(self::DATA_FILE);

        Log::info('AdminUserSeeder: loading admin users', ['path' => $path]);
        $this->command->info("  → Loading from {$path}");

        return json_decode(file_get_contents($path), true);
    }

    /**
     * Upserts the admin User row by email. If the user already exists, its
     * fields are updated to reflect the latest values from the JSON file.
     * The Spatie role key is stripped before upserting (assigned separately).
     *
     * @param  array<string, mixed>  $userData
     */
    protected function upsertAdminUser(array $userData): User
    {
        $email = $userData['email'];

        // Strip the Spatie role key — it is assigned separately.
        unset($userData['role']);

        // Convert boolean email_verified_at to a timestamp.
        if ($userData['email_verified_at'] === true) {
            $userData['email_verified_at'] = now();
        }

        // Hash the plain‑text password stored in the data file.
        $userData['password'] = Hash::make($userData['password']);

        $user = User::updateOrCreate(
            ['email' => $email],
            $userData
        );

        if ($user->wasRecentlyCreated) {
            Log::info('AdminUserSeeder: created user', ['email' => $email]);
            $this->command->info("  + Created user → {$email}");
        } else {
            Log::info('AdminUserSeeder: updated user', ['email' => $email]);
            $this->command->info("  ↻ Updated user → {$email}");
        }

        return $user;
    }

    /**
     * Assigns a Spatie role to the given User. The role row is assumed to
     * already exist (RoleSeeder runs before this seeder).
     */
    protected function assignRole(User $user, string $roleName): void
    {
        $user->assignRole($roleName);

        Log::info('AdminUserSeeder: assigned role', ['email' => $user->email, 'role' => $roleName]);
        $this->command->info("  ✓ Assigned role → {$roleName}");
    }
}

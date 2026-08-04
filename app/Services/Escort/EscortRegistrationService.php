<?php

namespace App\Services\Escort;

use App\Models\Escort;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Business logic for the public escort self-registration flow.
 *
 * Creates the linked User account (user_type = 'escort'), assigns the Spatie
 * 'escort' role, and creates the Escort profile with
 * verification_status = 'pending'. Everything runs inside one transaction so a
 * failure never leaves a half-registered application behind.
 *
 * The user-supplied password is used as-is (the User model's `hashed` cast
 * hashes it on save), so UserObserver does not generate a temp password or send
 * a welcome email — the approval/rejection notification is handled later by
 * EscortVerificationService.
 */
class EscortRegistrationService
{
    /**
     * Register a new escort application.
     *
     * @param  array<string, mixed>  $data  Validated request data.
     * @return User The newly created escort user.
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            // 1. Create the linked user account — auto-verified (matches the
            // social-login flow) so the API is not blocked by email walls.
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
                'password' => $data['password'],
                'user_type' => 'escort',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            // 2. Assign the Spatie escort role (used by display_name and
            //    frontend role checks; distinct from the user_type column).
            $user->assignRole(Role::firstOrCreate(['name' => 'escort']));

            // 3. Create the pending Escort profile — not visible to clients
            //    until an admin approves it via EscortVerificationService.
            Escort::create([
                'user_id' => $user->id,
                'stage_name' => $data['stage_name'],
                'bio' => $data['bio'] ?? null,
                'services' => $data['services'] ?? null,
                'rate_per_hour' => $data['rate_per_hour'] ?? null,
                'rate_per_night' => $data['rate_per_night'] ?? null,
                'incall_available' => $data['incall_available'] ?? false,
                'outcall_available' => $data['outcall_available'] ?? false,
                'available' => $data['available'] ?? true,
                'accepting_new_clients' => true,
                'verification_status' => 'pending',
                'is_verified' => false,
            ]);

            return $user;
        });
    }
}

<?php

namespace App\Filament\Admin\Resources\EscortResource\Pages;

use App\Filament\Admin\Resources\EscortResource;
use App\Models\Escort;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates an escort and its linked user account in a single transaction.
 *
 * The form submits a flat array containing both `user.*` fields (maps to
 * the users table) and escort-profile fields (maps to the escorts table).
 * This handler splits them, creates the User first, then creates the Escort
 * with the new user_id.
 */
class CreateEscort extends CreateRecord
{
    protected static string $resource = EscortResource::class;

    /**
     * @param  array  $data  Form data containing user.* + escort fields
     * @return Model|Escort The newly created Escort (user is created as a side-effect)
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $userData = $data['user'] ?? [];
            $escortData = $data;

            // Remove nested user fields so they aren't passed to Escort::create()
            unset($escortData['user']);

            // Create the linked user account
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'name' => trim(($userData['first_name'] ?? '').' '.($userData['last_name'] ?? '')),
                'email' => $userData['email'],
                'phone_number' => $userData['phone_number'] ?? null,
                'password' => Hash::make($userData['password'] ?? 'password123'),
                'user_type' => 'escort',
                'status' => 'active',
            ]);

            $user->assignRole('escort');

            // Link the escort profile to the new user
            $escortData['user_id'] = $user->id;

            return Escort::create($escortData);
        });
    }
}

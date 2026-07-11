<?php

namespace App\Filament\Admin\Resources\EscortResource\Pages;

use App\Filament\Admin\Resources\EscortResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Updates an escort and its linked user account in a single transaction.
 *
 * Like CreateEscort, the form data contains both `user.*` and escort
 * fields. Only the fields present in the request are applied — password
 * is only updated when a non-empty value is provided.
 */
class EditEscort extends EditRecord
{
    protected static string $resource = EscortResource::class;

    /**
     * @param  Model  $record  The Escort model being updated
     * @param  array  $data  Form data containing user.* + escort fields
     * @return Model The updated Escort model
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $userData = $data['user'] ?? [];
            $escortData = $data;

            unset($escortData['user']);

            // Sync the linked user's identity fields if provided
            if (! empty($userData)) {
                $user = $record->user;

                if (isset($userData['first_name'])) {
                    $user->first_name = $userData['first_name'];
                }
                if (isset($userData['last_name'])) {
                    $user->last_name = $userData['last_name'];
                }
                if (isset($userData['email'])) {
                    $user->email = $userData['email'];
                }
                if (isset($userData['phone_number'])) {
                    $user->phone_number = $userData['phone_number'];
                }
                if (! empty($userData['password'])) {
                    // Only hash when a new password is actually provided
                    $user->password = Hash::make($userData['password']);
                }

                $user->name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                $user->save();
            }

            $record->update($escortData);

            return $record;
        });
    }
}

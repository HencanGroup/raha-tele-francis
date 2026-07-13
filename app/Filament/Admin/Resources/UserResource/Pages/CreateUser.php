<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Creates a staff user. The temporary password + welcome email are handled by
 * UserObserver, so no password field is collected here.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Sets user_type to system_user so the observer generates a password and
     * sends the welcome email — the form only captures identity, status, roles.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['user_type'] = 'system_user';

        return parent::handleRecordCreation($data);
    }

    // Return to the list after creating, not the edit page.
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

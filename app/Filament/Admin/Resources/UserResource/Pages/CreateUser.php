<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Creates a staff user. The temporary password + welcome email are handled by
 * UserObserver, so no password field is collected here.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // Return to the list after creating, not the edit page.
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

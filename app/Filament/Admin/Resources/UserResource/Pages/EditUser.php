<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edits a staff user.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @return array<int, DeleteAction> The delete action in the page header.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // Return to the list after saving, not back to the edit page.
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

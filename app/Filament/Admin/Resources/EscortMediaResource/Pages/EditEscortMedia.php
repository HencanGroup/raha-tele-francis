<?php

namespace App\Filament\Admin\Resources\EscortMediaResource\Pages;

use App\Filament\Admin\Resources\EscortMediaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edits an escort media item — updates metadata, verification, and visibility.
 */
class EditEscortMedia extends EditRecord
{
    protected static string $resource = EscortMediaResource::class;

    /**
     * @return array<int, DeleteAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

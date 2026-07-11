<?php

namespace App\Filament\Admin\Resources\EscortResource\Pages;

use App\Filament\Admin\Resources\EscortResource;
use App\Filament\Admin\Resources\EscortResource\Tables\EscortsTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * Lists all escort records with a top-level Create button.
 */
class ListEscorts extends ListRecords
{
    protected static string $resource = EscortResource::class;

    /**
     * @return array<CreateAction> The create action visible to authorised admins
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return EscortsTable::configure($table);
    }
}

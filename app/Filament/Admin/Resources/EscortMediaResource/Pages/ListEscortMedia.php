<?php

namespace App\Filament\Admin\Resources\EscortMediaResource\Pages;

use App\Filament\Admin\Resources\EscortMediaResource;
use App\Filament\Admin\Resources\EscortMediaResource\Tables\EscortMediaTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * Lists all escort media items with a top-level Create button.
 */
class ListEscortMedia extends ListRecords
{
    protected static string $resource = EscortMediaResource::class;

    /**
     * @return array<int, CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return EscortMediaTable::configure($table);
    }
}

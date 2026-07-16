<?php

namespace App\Filament\Admin\Resources\SystemSettingResource\Pages;

use App\Filament\Admin\Resources\SystemSettingResource;
use App\Filament\Admin\Resources\SystemSettingResource\Tables\SystemSettingsTable;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * List page for SystemSetting records — delegates table to
 * SystemSettingsTable::configure() per the split-file convention.
 */
class ListSystemSettings extends ListRecords
{
    protected static string $resource = SystemSettingResource::class;

    public function table(Table $table): Table
    {
        return SystemSettingsTable::configure($table);
    }
}

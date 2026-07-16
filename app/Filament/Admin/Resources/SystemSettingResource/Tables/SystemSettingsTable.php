<?php

namespace App\Filament\Admin\Resources\SystemSettingResource\Tables;

use App\Filament\Exports\SystemSettingExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Table schema for the SystemSetting resource.
 *
 * Columns: key, value, type. Default sort by key ascending. Includes an
 * ExportAction inside the BulkActionGroup per AGENTS.md export rules.
 */
class SystemSettingsTable
{
    /**
     * Compose columns, default sort, and the bulk-actions dropdown.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            ->columns([
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'integer' => 'info',
                        'decimal' => 'warning',
                        'boolean' => 'success',
                        default => 'gray',
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportAction::make()
                        ->exporter(SystemSettingExporter::class)
                        ->fileName(fn (Export $export): string => "system-settings-{$export->getKey()}"),
                ]),
            ]);
    }
}

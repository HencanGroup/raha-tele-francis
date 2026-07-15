<?php

namespace App\Filament\Exports;

use App\Models\EscortResource;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the Admin → Escort Media table.
 *
 * Columns are declared explicitly — storage paths (path, thumbnail_path) are
 * excluded from the export as they reference internal storage URLs that are
 * not useful outside the panel. Runs on the database queue per AGENTS.md.
 */
class EscortMediaExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = EscortResource::class;

    /**
     * Columns written to the exported file (escort, type, visibility, sort, date).
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('escort.stage_name')->label('Escort'),
            ExportColumn::make('type')->label('Type'),
            ExportColumn::make('caption')->label('Caption'),
            ExportColumn::make('is_primary')->label('Primary'),
            ExportColumn::make('is_verified')->label('Verified'),
            ExportColumn::make('is_public')->label('Public'),
            ExportColumn::make('sort_order')->label('Sort Order'),
            ExportColumn::make('created_at')->label('Uploaded'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your escort media export is ready ('.$export->successful_rows.' rows).';
    }
}

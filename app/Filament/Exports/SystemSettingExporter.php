<?php

namespace App\Filament\Exports;

use App\Models\SystemSetting;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the SystemSetting table.
 *
 * Exports key, value, and type so staff can audit the configuration. No
 * sensitive fields exist on this model. Runs on the database queue per
 * AGENTS.md.
 */
class SystemSettingExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = SystemSetting::class;

    /**
     * Columns written to the exported file (key → value → type order).
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('key'),
            ExportColumn::make('value'),
            ExportColumn::make('type'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your system-settings export is ready ('.$export->successful_rows.' rows).';
    }
}

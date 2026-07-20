<?php

namespace App\Filament\Exports;

use App\Models\Report;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the Reports moderation table.
 *
 * Columns are declared explicitly; internal moderation notes are excluded.
 * Runs on the database queue per AGENTS.md.
 */
class ReportExporter extends Exporter
{
    protected static ?string $model = Report::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('reason'),
            ExportColumn::make('review.rating')->label('Review Rating'),
            ExportColumn::make('review.comment')->label('Review Comment'),
            ExportColumn::make('reporter.name')->label('Reported By'),
            ExportColumn::make('status'),
            ExportColumn::make('created_at')->label('Reported'),
            ExportColumn::make('resolved_at')->label('Resolved'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your reports export is ready ('.$export->successful_rows.' rows).';
    }
}

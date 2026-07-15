<?php

namespace App\Filament\Exports;

use App\Models\Review;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the Admin → Reviews table.
 *
 * Columns are declared explicitly — internal moderation notes or user PII
 * beyond the author name are never exported. Runs on the database queue
 * per AGENTS.md.
 */
class ReviewExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = Review::class;

    /**
     * Columns written to the exported file (rating, comment, moderation state, date).
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('rating')->label('Rating'),
            ExportColumn::make('comment')->label('Comment'),
            ExportColumn::make('is_verified')->label('Verified'),
            ExportColumn::make('is_visible')->label('Visible'),
            ExportColumn::make('created_at')->label('Submitted'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your reviews export is ready ('.$export->successful_rows.' rows).';
    }
}

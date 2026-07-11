<?php

namespace App\Filament\Exports;

use App\Models\Escort;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the Admin → Escorts table.
 *
 * Columns are declared explicitly. Verification documents, custom rates, and
 * any other operational internals are never exported — only identity, status,
 * public profile, and financial summary fields staff need for reporting.
 * Runs on the database queue per AGENTS.md.
 */
class EscortExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = Escort::class;

    /**
     * Columns written to the exported file.
     *
     * Order mirrors the table view: identity → verification → engagement →
     * financial → audit.
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            // Identity (from the linked user account).
            ExportColumn::make('stage_name')->label('Stage Name'),
            ExportColumn::make('user.first_name')->label('First Name'),
            ExportColumn::make('user.last_name')->label('Last Name'),
            ExportColumn::make('user.email')->label('Email'),
            ExportColumn::make('user.phone_number')->label('Phone'),

            // Verification & visibility state.
            ExportColumn::make('verification_status')->label('Verification'),
            ExportColumn::make('is_verified')->label('Verified'),
            ExportColumn::make('featured')->label('Featured'),

            // Engagement metrics.
            ExportColumn::make('rating')->label('Rating'),
            ExportColumn::make('review_count')->label('Reviews'),
            ExportColumn::make('total_bookings')->label('Bookings'),

            // Financial summary (KES).
            ExportColumn::make('earnings')->label('Total Earnings'),
            ExportColumn::make('balance')->label('Current Balance'),

            ExportColumn::make('created_at')->label('Registered'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your escorts export is ready ('.$export->successful_rows.' rows).';
    }
}

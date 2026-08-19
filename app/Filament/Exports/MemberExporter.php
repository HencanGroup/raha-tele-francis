<?php

namespace App\Filament\Exports;

use App\Models\Member;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the Admin → Members table.
 *
 * Columns are declared explicitly. The raw social `social_id` and avatar URL
 * are never exported (PII not needed for reporting) — only identity, the credit
 * wallet summary, and the social provider name. Runs on the database queue
 * per AGENTS.md.
 */
class MemberExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = Member::class;

    /**
     * Columns written to the exported file.
     *
     * Order mirrors the table view: identity → wallet → acquisition → audit.
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            // Identity (from the linked user account).
            ExportColumn::make('user.first_name')->label('First Name'),
            ExportColumn::make('user.last_name')->label('Last Name'),
            ExportColumn::make('user.email')->label('Email'),
            ExportColumn::make('user.phone_number')->label('Phone'),

            // Credit wallet summary.
            ExportColumn::make('total_credits_earned')->label('Total Deposits'),
            ExportColumn::make('total_credits_spent')->label('Total Spent'),
            ExportColumn::make('credits')->label('Credit Balance'),
            ExportColumn::make('credits_expire_at')->label('Credits Expire'),

            // Acquisition channel — provider name only, never the social id.
            ExportColumn::make('social_provider')->label('Social Login'),

            ExportColumn::make('created_at')->label('Member Since'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your members export is ready ('.$export->successful_rows.' rows).';
    }
}

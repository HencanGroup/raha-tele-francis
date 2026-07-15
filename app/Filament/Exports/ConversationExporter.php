<?php

namespace App\Filament\Exports;

use App\Models\Conversation;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the Admin → Conversations table.
 *
 * Columns are declared explicitly — participant email addresses are included
 * for support traceability, but internal per-side state flags are omitted
 * from the export (they are visible in the View infolist). Runs on the
 * database queue per AGENTS.md.
 */
class ConversationExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = Conversation::class;

    /**
     * Columns written to the exported file (participants, status, financials, dates).
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('userOne.email')->label('Participant A'),
            ExportColumn::make('userTwo.email')->label('Participant B'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('is_paid_conversation')->label('Paid'),
            ExportColumn::make('total_credits_spent')->label('Credits Spent'),
            ExportColumn::make('total_earnings')->label('Escort Earnings'),
            ExportColumn::make('last_message_at')->label('Last Message'),
            ExportColumn::make('created_at')->label('Started'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your conversations export is ready ('.$export->successful_rows.' rows).';
    }
}

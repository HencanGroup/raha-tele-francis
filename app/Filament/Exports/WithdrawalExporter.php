<?php

namespace App\Filament\Exports;

use App\Models\Withdrawal;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the Admin → Withdrawals table.
 *
 * Columns are declared explicitly (amounts in credits and KES for payout
 * reconciliation); recipient phone numbers are intentionally excluded because
 * they are personal data that staff rarely need in a bulk file. Runs on the
 * database queue per AGENTS.md.
 */
class WithdrawalExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = Withdrawal::class;

    /**
     * Columns written to the exported file (ledger order: who, what, status).
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('user.email')->label('Escort Email'),
            ExportColumn::make('amount')->label('Credits'),
            ExportColumn::make('amount_kes')->label('Amount (KES)'),
            ExportColumn::make('status'),
            ExportColumn::make('transaction_id')->label('Transaction ID'),
            ExportColumn::make('failure_reason')->label('Failure Reason'),
            ExportColumn::make('processed_at')->label('Processed At'),
            ExportColumn::make('created_at')->label('Requested At'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your withdrawals export is ready ('.$export->successful_rows.' rows).';
    }
}

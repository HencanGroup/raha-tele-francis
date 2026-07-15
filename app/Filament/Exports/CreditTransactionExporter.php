<?php

namespace App\Filament\Exports;

use App\Models\CreditTransaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the CreditTransaction ledger table.
 *
 * Columns are declared explicitly; balances are exported for reconciliation,
 * never internal-only fields. Runs on the database queue per AGENTS.md.
 */
class CreditTransactionExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = CreditTransaction::class;

    /**
     * Columns written to the exported file (ledger order: who, what, balances).
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.email')->label('User'),
            ExportColumn::make('type')->label('Type'),
            ExportColumn::make('amount')->label('Amount'),
            ExportColumn::make('balance_before')->label('Balance Before'),
            ExportColumn::make('balance_after')->label('Balance After'),
            ExportColumn::make('description')->label('Description'),
            ExportColumn::make('created_at')->label('Date'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your credit-transactions export is ready ('.$export->successful_rows.' rows).';
    }
}

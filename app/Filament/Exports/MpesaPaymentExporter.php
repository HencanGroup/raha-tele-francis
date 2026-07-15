<?php

namespace App\Filament\Exports;

use App\Models\MpesaPayment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the Admin → M-Pesa Payments table.
 *
 * Columns are declared explicitly — raw Daraja credentials or internal
 * processing fields are never exported. Runs on the database queue per AGENTS.md.
 */
class MpesaPaymentExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = MpesaPayment::class;

    /**
     * Columns written to the exported file (transaction, user, amounts, status, date).
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('transaction_id')->label('Transaction ID'),
            ExportColumn::make('user.email')->label('User'),
            ExportColumn::make('phone_number')->label('Phone'),
            ExportColumn::make('amount')->label('Amount (KES)'),
            ExportColumn::make('credits_awarded')->label('Credits Awarded'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('created_at')->label('Date'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your M-Pesa payments export is ready ('.$export->successful_rows.' rows).';
    }
}

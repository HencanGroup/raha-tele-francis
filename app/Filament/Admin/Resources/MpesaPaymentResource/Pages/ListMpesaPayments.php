<?php

namespace App\Filament\Admin\Resources\MpesaPaymentResource\Pages;

use App\Filament\Admin\Resources\MpesaPaymentResource;
use App\Filament\Admin\Resources\MpesaPaymentResource\Tables\MpesaPaymentsTable;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * Lists all M-Pesa payment records — read-only.
 */
class ListMpesaPayments extends ListRecords
{
    protected static string $resource = MpesaPaymentResource::class;

    public function table(Table $table): Table
    {
        return MpesaPaymentsTable::configure($table);
    }
}

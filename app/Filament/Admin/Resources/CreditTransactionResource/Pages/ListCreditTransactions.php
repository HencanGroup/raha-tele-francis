<?php

namespace App\Filament\Admin\Resources\CreditTransactionResource\Pages;

use App\Filament\Admin\Resources\CreditTransactionResource;
use App\Filament\Admin\Resources\CreditTransactionResource\Tables\CreditTransactionsTable;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * Lists all credit transactions (immutable ledger) — read-only.
 */
class ListCreditTransactions extends ListRecords
{
    protected static string $resource = CreditTransactionResource::class;

    public function table(Table $table): Table
    {
        return CreditTransactionsTable::configure($table);
    }
}

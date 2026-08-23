<?php

namespace App\Filament\Admin\Resources\CreditTransactionResource\Pages;

use App\Filament\Admin\Resources\CreditTransactionResource;
use App\Filament\Admin\Resources\CreditTransactionResource\Tables\CreditTransactionsTable;
use App\Filament\Admin\Resources\CreditTransactionResource\Widgets\CreditTransactionStatsOverview;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

/**
 * Lists all credit transactions (immutable ledger) — read-only.
 */
class ListCreditTransactions extends ListRecords
{
    protected static string $resource = CreditTransactionResource::class;

    /**
     * Monthly platform/escort/member totals rendered above the table.
     *
     * @return array<int, class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            CreditTransactionStatsOverview::class,
        ];
    }

    public function table(Table $table): Table
    {
        return CreditTransactionsTable::configure($table);
    }
}

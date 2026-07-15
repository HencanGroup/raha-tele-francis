<?php

namespace App\Filament\Admin\Resources\CreditTransactionResource\Pages;

use App\Filament\Admin\Resources\CreditTransactionResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Read-only detail view of a single credit transaction.
 *
 * Shows the full transaction details including before/after balances and the
 * polymorphic reference, if any.
 */
class ViewCreditTransaction extends ViewRecord
{
    protected static string $resource = CreditTransactionResource::class;

    /**
     * Builds the read-only infolist layout for a credit transaction.
     */
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->schema([
                        TextEntry::make('user.email')->label('User'),
                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'purchase' => 'success',
                                'usage' => 'danger',
                                'bonus' => 'info',
                                'withdrawal' => 'warning',
                                'refund' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('amount')->label('Amount')->numeric(2),
                        TextEntry::make('balance_before')->label('Balance Before')->numeric(2),
                        TextEntry::make('balance_after')->label('Balance After')->numeric(2),
                        TextEntry::make('description')->label('Description'),
                        TextEntry::make('created_at')->label('Date')->dateTime(),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\CreditTransactionResource\Tables;

use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\CreditTransactionExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table schema for the Admin → Credit Transactions resource (immutable ledger).
 *
 * Composes columns (user, type, amounts, dates), a type select filter, a
 * created-at date-range filter, and a bulk-actions dropdown with Export only.
 * No DeleteBulkAction — ledger rows are immutable per AGENTS.md.
 */
class CreditTransactionsTable
{
    // Shared from/to date-range filter — see "Filament Table Rules" in AGENTS.md.
    use HasDateRangeFilter;

    /**
     * Compose the ledger list: columns, filters, and the Export action.
     *
     * Newest transactions first; the `user` relation is eager-loaded upstream in
     * CreditTransactionResource::getEloquentQuery() so identity columns avoid N+1.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    // Colour signals the direction of credit movement.
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'success',
                        'usage' => 'danger',
                        'bonus' => 'info',
                        'withdrawal' => 'warning',
                        'refund' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('balance_before')
                    ->label('Balance Before')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // Transaction type — narrows the view to purchases, usage, etc.
                Filter::make('type')
                    ->schema([
                        Select::make('value')
                            ->label('Type')
                            ->options([
                                'purchase' => 'Purchase',
                                'usage' => 'Usage',
                                'bonus' => 'Bonus',
                                'withdrawal' => 'Withdrawal',
                                'refund' => 'Refund',
                            ])
                            ->placeholder('All types'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $type): Builder => $q->where('type', $type),
                    )),

                // Transaction date-range — parsing/clamping delegated to the trait.
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('to')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::applyDateRangeFilter(
                        $query,
                        $data,
                        column: 'created_at',
                        defaultMonths: 2,
                        maxMonths: 3,
                    )),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                // Ledger rows are immutable — Export is the only bulk action.
                BulkActionGroup::make([
                    ExportAction::make()
                        ->exporter(CreditTransactionExporter::class)
                        ->fileName(fn (Export $export): string => "credit-transactions-{$export->getKey()}"),
                ]),
            ]);
    }
}

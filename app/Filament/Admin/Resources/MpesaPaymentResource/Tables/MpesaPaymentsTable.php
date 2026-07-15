<?php

namespace App\Filament\Admin\Resources\MpesaPaymentResource\Tables;

use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\MpesaPaymentExporter;
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
 * Table schema for the Admin → M-Pesa Payments resource (read-only).
 *
 * Composes columns (transaction ID, user, amount, status, date), a status
 * select filter, a date-range filter, and a bulk-actions dropdown with Export
 * only. No DeleteBulkAction — payment records are immutable per AGENTS.md.
 */
class MpesaPaymentsTable
{
    // Shared from/to date-range filter — see "Filament Table Rules" in AGENTS.md.
    use HasDateRangeFilter;

    /**
     * Compose the payment list: columns, filters, and the Export action.
     *
     * Newest payments first; the `user` relation is eager-loaded upstream in
     * MpesaPaymentResource::getEloquentQuery() so identity columns avoid N+1.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone_number')
                    ->label('Phone'),

                TextColumn::make('amount')
                    ->label('Amount (KES)')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('credits_awarded')
                    ->label('Credits Awarded')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    // Colour signals payment state at a glance.
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->limit(20)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // Payment status — narrows to completed, pending, or failed.
                Filter::make('status')
                    ->schema([
                        Select::make('value')
                            ->label('Status')
                            ->options([
                                'completed' => 'Completed',
                                'pending' => 'Pending',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->placeholder('All statuses'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $status): Builder => $q->where('status', $status),
                    )),

                // Payment date-range — parsing/clamping delegated to the trait.
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
                // Payment records are immutable — Export is the only bulk action.
                BulkActionGroup::make([
                    ExportAction::make()
                        ->exporter(MpesaPaymentExporter::class)
                        ->fileName(fn (Export $export): string => "mpesa-payments-{$export->getKey()}"),
                ]),
            ]);
    }
}

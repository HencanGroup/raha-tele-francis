<?php

namespace App\Filament\Admin\Resources\WithdrawalResource\Tables;

use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\WithdrawalExporter;
use App\Models\Withdrawal;
use App\Services\Withdrawal\WithdrawalService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table schema for the Admin → Withdrawals resource.
 *
 * Composes columns (escort, amounts, phone, status, settlement), a status
 * select filter, a date-range filter, row actions (approve / refund) that
 * delegate to WithdrawalService, and a bulk-actions dropdown with Export only.
 * No DeleteBulkAction — withdrawals are immutable financial records per
 * AGENTS.md.
 */
class WithdrawalsTable
{
    // Shared from/to date-range filter — see "Filament Table Rules" in AGENTS.md.
    use HasDateRangeFilter;

    /**
     * Compose the withdrawal list: columns, filters, row actions, and Export.
     *
     * Newest withdrawals first; the `user` relation is eager-loaded upstream in
     * WithdrawalResource::getEloquentQuery() so identity columns avoid N+1.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Escort')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Credits')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('amount_kes')
                    ->label('Amount (KES)')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('phone_number')
                    ->label('Phone'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    // Colour signals the payout lifecycle at a glance.
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('mpesa_reference')
                    ->label('M-Pesa Ref')
                    ->limit(20)
                    ->toggleable(),

                TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // Payout status — narrows to pending, processing, completed, failed.
                Filter::make('status')
                    ->schema([
                        Select::make('value')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->placeholder('All statuses'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $status): Builder => $q->where('status', $status),
                    )),

                // Request date-range — parsing/clamping delegated to the trait.
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Requested from'),
                        DatePicker::make('to')->label('Requested until'),
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
            ->recordActions([
                // All state-changing actions delegate to WithdrawalService.
                ActionGroup::make([
                    // Submits the M-Pesa B2C payout and marks the withdrawal processing.
                    Action::make('approve')
                        ->label('Approve & Pay')
                        ->icon('heroicon-o-banknotes')
                        ->action(function (Withdrawal $record, WithdrawalService $withdrawalService) {
                            try {
                                $withdrawalService->approve($record, auth()->user());
                                Notification::make()
                                    ->success()
                                    ->title('Withdrawal approved — B2C payout submitted.')
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->danger()
                                    ->title($e->getMessage())
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn (Withdrawal $record): bool => $record->status === Withdrawal::STATUS_PENDING),

                    // Restores the reserved credits when a payout fails or is rejected.
                    Action::make('refund')
                        ->label('Mark Failed & Refund')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->action(function (Withdrawal $record, WithdrawalService $withdrawalService) {
                            $withdrawalService->refund($record, 'Marked failed by admin');
                            Notification::make()
                                ->success()
                                ->title('Withdrawal marked failed and credits refunded.')
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(fn (Withdrawal $record): bool => in_array($record->status, [
                            Withdrawal::STATUS_PENDING,
                            Withdrawal::STATUS_PROCESSING,
                        ], true)),
                ]),
            ])
            ->toolbarActions([
                // Withdrawals are immutable — Export is the only bulk action.
                BulkActionGroup::make([
                    ExportAction::make()
                        ->exporter(WithdrawalExporter::class)
                        ->fileName(fn (Export $export): string => "withdrawals-{$export->getKey()}"),
                ]),
            ]);
    }
}

<?php

namespace App\Livewire;

use App\Models\CreditTransaction;
use App\Models\Escort;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Database\Eloquent\Builder;

/**
 * Standalone Livewire component rendering a real Filament Table of
 * CreditTransaction rows for a given escort.
 *
 * Receives the escort ID, resolves the user_id, and queries transactions.
 * Embedded inside the ViewEscort infolist "Earnings" tab via Livewire::make().
 */
class EscortTransactionsTable extends TableComponent
{
    public int $escortId;

    public function mount(int $escortId): void
    {
        $this->escortId = $escortId;
    }

    public function table(Table $table): Table
    {
        $userId = Escort::where('id', $this->escortId)->value('user_id');

        return $table
            ->query(
                fn (): Builder => CreditTransaction::query()->where('user_id', $userId ?? 0)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'success',
                        'usage' => 'danger',
                        'bonus' => 'info',
                        'withdrawal' => 'warning',
                        'commission' => 'primary',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('balance_before')
                    ->label('Balance Before')
                    ->money('KES'),

                TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->money('KES'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'purchase' => 'Purchase',
                        'usage' => 'Usage',
                        'bonus' => 'Bonus',
                        'withdrawal' => 'Withdrawal',
                        'commission' => 'Commission',
                    ])
                    ->multiple(),
            ])
            ->searchable(['type', 'description'])
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.escort-transactions-table');
    }
}

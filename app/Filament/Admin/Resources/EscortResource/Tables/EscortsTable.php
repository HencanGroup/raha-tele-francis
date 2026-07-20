<?php

namespace App\Filament\Admin\Resources\EscortResource\Tables;

use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\EscortExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table schema for the Admin → Escorts resource.
 *
 * Composes columns (identity, verification, engagement, financial), a
 * registration date-range filter, and a bulk-actions dropdown. The Export
 * action lives inside the BulkActionGroup per AGENTS.md.
 */
class EscortsTable
{
    // Shared from/to date-range filter — see "Filament Table Rules" in AGENTS.md.
    use HasDateRangeFilter;

    /**
     * Compose the escort list: columns, date filter, and bulk actions.
     *
     * Newest escorts first; the `user` relation is eager-loaded upstream in
     * EscortResource::getEloquentQuery() so the identity columns avoid N+1.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stage_name')
                    ->label('Stage Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.phone_number')
                    ->label('Phone'),

                TextColumn::make('verification_status')
                    ->label('Verification')
                    ->badge()
                    // Colour signals moderation state at a glance.
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('earnings')
                    ->label('Earnings')
                    ->numeric(2)
                    ->sortable()
                    // Off by default — financial detail, not needed at a glance.
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // Verification status — lets admins quickly isolate pending applications.
                Filter::make('verification_status')
                    ->label('Verification Status')
                    ->schema([
                        Select::make('value')
                            ->label('Verification Status')
                            ->options([
                                'pending' => 'Pending',
                                'verified' => 'Verified',
                                'rejected' => 'Rejected',
                            ])
                            ->placeholder('All'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $data['value'] === null
                        ? $query
                        : $query->where('verification_status', $data['value'])
                    ),

                // Registration date-range — parsing/clamping delegated to the trait.
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Registered from'),
                        DatePicker::make('to')->label('Registered until'),
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
                ViewAction::make(),
            ])
            ->toolbarActions([
                // All "act on selected rows" buttons live under one dropdown.
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    // Export the selected escorts (or the full filtered set).
                    ExportBulkAction::make()
                        ->exporter(EscortExporter::class)
                        // File name carries exports.id so support can trace it back.
                        ->fileName(fn (Export $export): string => "escorts-{$export->getKey()}"),
                ]),
            ]);
    }
}

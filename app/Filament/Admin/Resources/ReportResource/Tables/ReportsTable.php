<?php

namespace App\Filament\Admin\Resources\ReportResource\Tables;

use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\ReportExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table schema for the Admin → Reports resource.
 *
 * Composes columns (reason, review, reporter, status), status and reason
 * filters, a created-at date filter, and a bulk-actions dropdown. The Export
 * action lives inside the BulkActionGroup per AGENTS.md.
 */
class ReportsTable
{
    use HasDateRangeFilter;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->sortable(),

                TextColumn::make('review.rating')
                    ->label('Review Rating')
                    ->numeric(0)
                    ->sortable(),

                TextColumn::make('review.comment')
                    ->label('Review Comment')
                    ->limit(40),

                TextColumn::make('reporter.name')
                    ->label('Reported By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'resolved' => 'success',
                        'dismissed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Reported')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('status')
                    ->label('Status')
                    ->schema([
                        Select::make('value')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'resolved' => 'Resolved',
                                'dismissed' => 'Dismissed',
                            ])
                            ->placeholder('All'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $data['value'] === null
                        ? $query
                        : $query->where('status', $data['value'])
                    ),

                Filter::make('reason')
                    ->label('Reason')
                    ->schema([
                        Select::make('value')
                            ->label('Reason')
                            ->options([
                                'inappropriate' => 'Inappropriate',
                                'spam' => 'Spam',
                                'fake_profile' => 'Fake Profile',
                                'harassment' => 'Harassment',
                                'other' => 'Other',
                            ])
                            ->placeholder('All'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $data['value'] === null
                        ? $query
                        : $query->where('reason', $data['value'])
                    ),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Reported from'),
                        DatePicker::make('to')->label('Reported until'),
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
                BulkActionGroup::make([
                    ExportAction::make()
                        ->exporter(ReportExporter::class)
                        ->fileName(fn (Export $export): string => "reports-{$export->getKey()}"),
                ]),
            ]);
    }
}

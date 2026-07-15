<?php

namespace App\Filament\Admin\Resources\ReviewResource\Tables;

use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\ReviewExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table schema for the Admin → Reviews resource.
 *
 * Composes columns (rating, comment, author, escort, moderation state), a
 * created-at date-range filter, and a bulk-actions dropdown. The Export action
 * lives inside the BulkActionGroup per AGENTS.md.
 */
class ReviewsTable
{
    // Shared from/to date-range filter — see "Filament Table Rules" in AGENTS.md.
    use HasDateRangeFilter;

    /**
     * Compose the review list: columns, date filter, row actions, and bulk actions.
     *
     * Newest reviews first; the `user` and `escort` relations are eager-loaded
     * upstream in ReviewResource::getEloquentQuery() so identity columns avoid N+1.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rating')
                    ->label('Rating')
                    ->numeric(0)
                    ->sortable(),

                TextColumn::make('comment')
                    ->label('Comment')
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('escort.stage_name')
                    ->label('Escort')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('is_verified')
                    ->label('Verified')
                    ->badge()
                    // Green when verified, gray when pending.
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Verified' : 'Pending'),

                TextColumn::make('is_visible')
                    ->label('Visible')
                    ->badge()
                    // Green when visible, danger when hidden.
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Visible' : 'Hidden'),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // Submission date-range — parsing/clamping delegated to the trait.
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Submitted from'),
                        DatePicker::make('to')->label('Submitted until'),
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
                EditAction::make(),
            ])
            ->toolbarActions([
                // All "act on selected rows" buttons live under one dropdown.
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    // Export the selected reviews (or the full filtered set).
                    ExportAction::make()
                        ->exporter(ReviewExporter::class)
                        // File name carries exports.id so support can trace it back.
                        ->fileName(fn (Export $export): string => "reviews-{$export->getKey()}"),
                ]),
            ]);
    }
}

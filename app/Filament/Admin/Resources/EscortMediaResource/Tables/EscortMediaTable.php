<?php

namespace App\Filament\Admin\Resources\EscortMediaResource\Tables;

use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\EscortMediaExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table schema for the Admin → Escort Media resource.
 *
 * Composes columns (escort, type badge, visibility badges, sort order, date),
 * a type filter, an escort filter, a date-range filter, and a bulk-actions
 * dropdown with Delete and Export.
 */
class EscortMediaTable
{
    // Shared from/to date-range filter — see "Filament Table Rules" in AGENTS.md.
    use HasDateRangeFilter;

    /**
     * Compose the media list: columns, filters, and bulk actions.
     *
     * Newest first; the `escort` relation is eager-loaded upstream in
     * EscortMediaResource::getEloquentQuery() so the escort name column
     * avoids N+1.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('escort.stage_name')
                    ->label('Escort')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    // Colour distinguishes photos from videos at a glance.
                    ->color(fn (string $state): string => match ($state) {
                        'photo' => 'info',
                        'video' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('caption')
                    ->label('Caption')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('is_primary')
                    ->label('Primary')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),

                TextColumn::make('is_verified')
                    ->label('Verified')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),

                TextColumn::make('is_public')
                    ->label('Public')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),

                TextColumn::make('sort_order')
                    ->label('Sort')
                    ->numeric(0)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // Media type — narrow to photos or videos.
                Filter::make('type')
                    ->schema([
                        Select::make('value')
                            ->label('Type')
                            ->options([
                                'photo' => 'Photo',
                                'video' => 'Video',
                            ])
                            ->placeholder('All types'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $type): Builder => $q->where('type', $type),
                    )),

                // Upload date-range — parsing/clamping delegated to the trait.
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Uploaded from'),
                        DatePicker::make('to')->label('Uploaded until'),
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
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    ExportAction::make()
                        ->exporter(EscortMediaExporter::class)
                        ->fileName(fn (Export $export): string => "escort-media-{$export->getKey()}"),
                ]),
            ]);
    }
}

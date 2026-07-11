<?php

namespace App\Filament\Admin\Resources\MemberResource\Tables;

use App\Filament\Admin\Resources\MemberResource\Pages\ViewMember;
use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\MemberExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table schema for the Admin → Members resource (read-only).
 *
 * Composes columns (identity, wallet, acquisition), a "member since"
 * date-range filter, and a bulk Export action. Rows link to the read-only
 * ViewMember detail page. No delete/edit bulk actions — members are managed
 * through the API, not the panel.
 */
class MembersTable
{
    // Shared from/to date-range filter — see "Filament Table Rules" in AGENTS.md.
    use HasDateRangeFilter;

    /**
     * Compose the member list: columns, date filter, and the Export action.
     *
     * Newest members first; the `user` relation is eager-loaded upstream in
     * MemberResource::getEloquentQuery() so identity columns avoid N+1.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.phone_number')
                    ->label('Phone'),

                TextColumn::make('credits')
                    ->label('Credits')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('total_credits_earned')
                    ->label('Total Earned')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_credits_spent')
                    ->label('Total Spent')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('social_provider')
                    ->label('Social Login')
                    ->badge()
                    // Brand colour per OAuth provider; grey when signed up by email.
                    ->color(fn (?string $state): string => match ($state) {
                        'google' => 'danger',
                        'facebook' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Member Since')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // "Member since" date-range — parsing/clamping delegated to the trait.
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Joined from'),
                        DatePicker::make('to')->label('Joined until'),
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
                    // Members are read-only here — export is the only bulk action.
                    ExportBulkAction::make()
                        ->exporter(MemberExporter::class)
                        // File name carries exports.id so support can trace it back.
                        ->fileName(fn (Export $export): string => "members-{$export->getKey()}"),
                ]),
            ])
            ->recordUrl(fn ($record): string => ViewMember::getUrl(['record' => $record]));
    }
}

<?php

namespace App\Filament\Admin\Resources\ConversationResource\Tables;

use App\Filament\Concerns\HasDateRangeFilter;
use App\Filament\Exports\ConversationExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table schema for the Admin → Conversations resource (read-only).
 *
 * Composes columns (participants, status, paid-chat badge, dates), a
 * paid-conversation toggle filter, a date-range filter, and a bulk-actions
 * dropdown with Export only. Conversations are immutable in the admin panel.
 */
class ConversationsTable
{
    // Shared from/to date-range filter — see "Filament Table Rules" in AGENTS.md.
    use HasDateRangeFilter;

    /**
     * Compose the conversation list: columns, filters, and the Export action.
     *
     * Most recent activity first; the `userOne` and `userTwo` relations are
     * eager-loaded upstream in ConversationResource::getEloquentQuery() so
     * participant columns avoid N+1.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_one_email')
                    ->label('Participant A')
                    // Resolved from the loaded relation so we don't trigger an extra query.
                    ->getStateUsing(fn ($record): string => $record->userOne->email ?? '—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user_two_email')
                    ->label('Participant B')
                    ->getStateUsing(fn ($record): string => $record->userTwo->email ?? '—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    // Colour signals the conversation's current state.
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'archived' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('is_paid_conversation')
                    ->label('Paid')
                    ->badge()
                    // Visible badge only when the conversation has a paywall.
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),

                TextColumn::make('total_credits_spent')
                    ->label('Credits Spent')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_earnings')
                    ->label('Escort Earnings')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('last_message_at')
                    ->label('Last Message')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // Narrow to paid conversations only.
                Filter::make('is_paid_conversation')
                    ->schema([
                        Toggle::make('value')->label('Paid conversations only'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? false,
                        fn (Builder $q): Builder => $q->where('is_paid_conversation', true),
                    )),

                // "Last message" date-range — parsing/clamping delegated to the trait.
                Filter::make('last_message_at')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('to')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::applyDateRangeFilter(
                        $query,
                        $data,
                        column: 'last_message_at',
                        defaultMonths: 2,
                        maxMonths: 3,
                    )),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->toolbarActions([
                // Conversations are immutable — Export is the only bulk action.
                BulkActionGroup::make([
                    ExportAction::make()
                        ->exporter(ConversationExporter::class)
                        ->fileName(fn (Export $export): string => "conversations-{$export->getKey()}"),
                ]),
            ]);
    }
}

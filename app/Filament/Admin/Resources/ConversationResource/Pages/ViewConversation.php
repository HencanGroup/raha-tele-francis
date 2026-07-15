<?php

namespace App\Filament\Admin\Resources\ConversationResource\Pages;

use App\Filament\Admin\Resources\ConversationResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Read-only detail view of a single conversation.
 *
 * Shows participants, per-side flags (mute/archive/block), and paid-chat
 * financials (credits spent, earnings, payer).
 */
class ViewConversation extends ViewRecord
{
    protected static string $resource = ConversationResource::class;

    /**
     * Builds the read-only infolist layout for a conversation.
     */
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Participants')
                    ->schema([
                        Group::make([
                            TextEntry::make('userOne.email')
                                ->label('Participant A')
                                ->default('—'),
                            TextEntry::make('userTwo.email')
                                ->label('Participant B')
                                ->default('—'),
                        ])->columns(2),

                        Group::make([
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'active' => 'success',
                                    'archived' => 'warning',
                                    'blocked' => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('created_at')
                                ->label('Started')
                                ->dateTime(),
                        ])->columns(2),
                    ]),

                Section::make('Per-Side State')
                    ->schema([
                        Group::make([
                            TextEntry::make('user_one_muted')
                                ->label('A Muted')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                            TextEntry::make('user_two_muted')
                                ->label('B Muted')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                        ])->columns(2),

                        Group::make([
                            TextEntry::make('user_one_archived')
                                ->label('A Archived')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                            TextEntry::make('user_two_archived')
                                ->label('B Archived')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                        ])->columns(2),

                        Group::make([
                            TextEntry::make('user_one_blocked')
                                ->label('A Blocked')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                            TextEntry::make('user_two_blocked')
                                ->label('B Blocked')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                        ])->columns(2),
                    ]),

                Section::make('Paid Conversation')
                    // Only relevant when the conversation is behind a paywall.
                    ->visible(fn ($record): bool => (bool) $record->is_paid_conversation)
                    ->schema([
                        Group::make([
                            TextEntry::make('is_paid_conversation')
                                ->label('Paid')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                            TextEntry::make('creditPayer.email')
                                ->label('Credit Payer')
                                ->default('—'),
                        ])->columns(2),

                        Group::make([
                            TextEntry::make('total_credits_spent')
                                ->label('Total Credits Spent')
                                ->numeric(2),
                            TextEntry::make('total_earnings')
                                ->label('Escort Earnings')
                                ->numeric(2),
                        ])->columns(2),
                    ]),
            ]);
    }
}

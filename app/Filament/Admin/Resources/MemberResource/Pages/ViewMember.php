<?php

namespace App\Filament\Admin\Resources\MemberResource\Pages;

use App\Filament\Admin\Resources\MemberResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Read-only detail view of a member's profile, wallet, and social login.
 */
class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    /**
     * Builds the read-only infolist layout for a member record.
     *
     * @param  Schema  $schema  The infolist schema container
     * @return Schema The populated schema with user info, wallet, and social sections
     */
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        Group::make([
                            TextEntry::make('user.first_name')->label('First Name'),
                            TextEntry::make('user.last_name')->label('Last Name'),
                        ])->columns(2),

                        Group::make([
                            TextEntry::make('user.email')->label('Email'),
                            TextEntry::make('user.phone_number')->label('Phone'),
                        ])->columns(2),

                        Group::make([
                            TextEntry::make('user.gender')->label('Gender'),
                            TextEntry::make('user.date_of_birth')->label('Date of Birth')->date(),
                        ])->columns(2),

                        TextEntry::make('user.status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'suspended' => 'danger',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Wallet')
                    ->schema([
                        Group::make([
                            TextEntry::make('total_credits_earned')->label('Total Deposits')->numeric(2),
                            TextEntry::make('total_credits_spent')->label('Total Spent')->numeric(2),
                        ])->columns(2),

                        Group::make([
                            TextEntry::make('credits')->label('Credit Balance')->numeric(2),
                            TextEntry::make('credits_expire_at')->label('Expires At')->dateTime(),
                        ])->columns(2),

                        TextEntry::make('last_credit_purchase_at')
                            ->label('Last Purchase')
                            ->dateTime(),
                    ]),

                // Social login section — only shown for members who signed up with OAuth
                Section::make('Social Login')
                    ->schema([
                        TextEntry::make('social_provider')->label('Provider'),
                        TextEntry::make('social_id')->label('Social ID'),
                        TextEntry::make('social_avatar')->label('Avatar URL'),
                    ])
                    ->visible(fn ($record): bool => $record->social_provider !== null),
            ]);
    }
}

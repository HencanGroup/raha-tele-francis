<?php

namespace App\Filament\Admin\Resources\EscortResource\Schemas;

use App\Livewire\EscortTransactionsTable;
use App\Models\Escort;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Infolist schema for the Escort view page (ViewEscort).
 *
 * Composes three tabs: General (account, profile, rates, services, etc.),
 * Photos (gallery of uploaded images), and Earnings (financial summary +
 * paginated Filament Table of transaction history). Each tab carries titled,
 * described, and icon-led sections that stack at full width.
 */
class EscortInfolist
{
    /**
     * Main infolist layout — composes the two tabs at full width.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Escort Details')
                ->persistTabInQueryString()
                ->tabs([
                    self::generalTab(),
                    self::photosTab(),
                    self::earningsTab(),
                ])
                ->columnSpanFull(),
        ]);
    }

    /* ── Tabs ── */

    /**
     * General tab — account, profile, physical attributes, rates,
     * services, availability, financial, and location.
     */
    protected static function generalTab(): Tab
    {
        return Tab::make('General')
            ->schema([
                self::userAccountSection(),
                self::profileSection(),
                self::physicalSection(),
                self::ratesSection(),
                self::servicesSection(),
                self::availabilitySection(),
                self::financialSection(),
                self::locationSection(),
            ]);
    }

    /**
     * Photos tab — media gallery with a fallback message when empty.
     */
    protected static function photosTab(): Tab
    {
        return Tab::make('Photos')
            ->schema([
                self::gallerySection(),
                self::noMediaFallback(),
            ]);
    }

    /* ── General sections ── */

    /**
     * Linked user account — name, email, phone, and account-status badge.
     */
    protected static function userAccountSection(): Section
    {
        return Section::make('User Account')
            ->description('Login identity for this escort.')
            ->icon(Heroicon::OutlinedUser)
            ->columns(2)
            ->schema([
                TextEntry::make('user.first_name')->label('First Name'),
                TextEntry::make('user.last_name')->label('Last Name'),
                TextEntry::make('user.email')->label('Email'),
                TextEntry::make('user.phone_number')->label('Phone'),
                TextEntry::make('user.status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'suspended' => 'danger',
                        'banned' => 'danger',
                        default => 'gray',
                    }),
            ]);
    }

    /**
     * Public profile — stage name, bio, verification badge, and boolean toggles.
     */
    protected static function profileSection(): Section
    {
        return Section::make('Profile')
            ->description('Public-facing bio, stage name, and verification state.')
            ->icon(Heroicon::OutlinedStar)
            ->columns(2)
            ->schema([
                TextEntry::make('stage_name')->label('Stage Name'),
                TextEntry::make('bio')->label('Bio')->columnSpanFull(),
                TextEntry::make('verification_status')
                    ->label('Verification')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                IconEntry::make('is_verified')->label('Verified')->boolean(),
                IconEntry::make('featured')->label('Featured')->boolean(),
                IconEntry::make('available')->label('Available')->boolean(),
                IconEntry::make('accepting_new_clients')
                    ->label('Accepting New Clients')
                    ->boolean(),
            ]);
    }

    /**
     * Optional appearance details — height, weight, body type, hair, eye colour.
     */
    protected static function physicalSection(): Section
    {
        return Section::make('Physical Attributes')
            ->description('Optional appearance details shown on the public profile.')
            ->icon(Heroicon::OutlinedIdentification)
            ->columns(2)
            ->schema([
                TextEntry::make('height')->label('Height (cm)')->numeric(2),
                TextEntry::make('weight')->label('Weight (kg)')->numeric(2),
                TextEntry::make('body_type')->label('Body Type'),
                TextEntry::make('hair_color')->label('Hair Color'),
                TextEntry::make('eye_color')->label('Eye Color'),
            ]);
    }

    /**
     * Hourly and nightly rates, in Kenyan Shillings.
     */
    protected static function ratesSection(): Section
    {
        return Section::make('Rates')
            ->description('Charges in Kenyan Shillings.')
            ->icon(Heroicon::OutlinedBanknotes)
            ->columns(2)
            ->schema([
                TextEntry::make('rate_per_hour')
                    ->label('Rate Per Hour (KES)')
                    ->money('KES'),
                TextEntry::make('rate_per_night')
                    ->label('Rate Per Night (KES)')
                    ->money('KES'),
            ]);
    }

    /**
     * Services offered and working hours.
     */
    protected static function servicesSection(): Section
    {
        return Section::make('Services')
            ->description('Services offered and working hours.')
            ->icon(Heroicon::OutlinedSparkles)
            ->schema([
                TextEntry::make('services')
                    ->label('Services Offered')
                    ->formatStateUsing(
                        fn ($state): string => is_array($state)
                        ? implode(', ', $state)
                        : ($state ?? '—')
                    ),
                TextEntry::make('working_hours')->label('Working Hours'),
            ]);
    }

    /**
     * Incall / outcall availability toggles.
     */
    protected static function availabilitySection(): Section
    {
        return Section::make('Availability')
            ->description('Incall / outcall options.')
            ->icon(Heroicon::OutlinedClock)
            ->columns(2)
            ->schema([
                IconEntry::make('incall_available')
                    ->label('Incall Available')
                    ->boolean(),
                IconEntry::make('outcall_available')
                    ->label('Outcall Available')
                    ->boolean(),
            ]);
    }

    /**
     * County and town linked to the user account.
     */
    protected static function locationSection(): Section
    {
        return Section::make('Location')
            ->description('County and town associated with the user account.')
            ->icon(Heroicon::OutlinedMapPin)
            ->columns(2)
            ->schema([
                TextEntry::make('user.county.name')
                    ->label('County')
                    ->default('—'),
                TextEntry::make('user.town.name')
                    ->label('Town')
                    ->default('—'),
            ]);
    }

    /* ── Photos sections ── */

    /**
     * Media gallery — grid of image thumbnails with captions.
     */
    protected static function gallerySection(): Section
    {
        return Section::make('Gallery')
            ->description('All media uploaded by this escort — photos and videos.')
            ->icon(Heroicon::OutlinedPhoto)
            ->schema([
                RepeatableEntry::make('resources')
                    ->label('')
                    ->hiddenLabel()
                    ->grid(3)
                    ->schema([
                        ImageEntry::make('path')
                            ->label('')
                            ->height(200)
                            ->extraImgAttributes(['class' => 'rounded object-cover w-full h-48']),
                        TextEntry::make('caption')
                            ->label('')
                            ->hiddenLabel()
                            ->placeholder('No caption'),
                    ])
                    ->visible(fn ($record): bool => $record->resources->isNotEmpty()),
            ]);
    }

    /**
     * Earnings and current balance — read-only financial summary.
     */
    protected static function financialSection(): Section
    {
        return Section::make('Financial')
            ->description('Earnings and current balance.')
            ->icon(Heroicon::OutlinedWallet)
            ->columns(2)
            ->schema([
                TextEntry::make('earnings')
                    ->label('Total Earnings')
                    ->money('KES'),
                TextEntry::make('balance')
                    ->label('Current Balance')
                    ->money('KES'),
            ]);
    }

    /* ── Earnings tab ── */

    /**
     * Earnings tab — financial summary (earnings + balance) followed by a
     * paginated Filament Table of all credit transactions for this escort.
     *
     * The transaction table is rendered via a ViewEntry that embeds the
     * EscortTransactionsTable Livewire component, using the escort ID as
     * state to query its user_id ledger rows.
     */
    protected static function earningsTab(): Tab
    {
        return Tab::make('Earnings')
            ->schema([
                self::earningsSummarySection(),
                self::transactionsSection(),
            ]);
    }

    /**
     * Paginated transaction history rendered as a real Filament Table
     * inside the earnings tab via the EscortTransactionsTable Livewire
     * component.
     */
    protected static function transactionsSection(): Section
    {
        return Section::make('Transaction History')
            ->description('All credit movements for this escort — purchases, usage, bonuses, withdrawals, and commissions.')
            ->icon(Heroicon::OutlinedTableCells)
            ->schema([
                Livewire::make(EscortTransactionsTable::class)
                    ->data(fn (?Escort $record): array => ['escortId' => $record?->id ?? 0]),
            ]);
    }

    /**
     * Financial summary — total lifetime earnings and current balance.
     */
    protected static function earningsSummarySection(): Section
    {
        return Section::make('Financial Summary')
            ->description('Lifetime earnings and current withdrawable balance.')
            ->icon(Heroicon::OutlinedBanknotes)
            ->columns(2)
            ->schema([
                TextEntry::make('earnings')
                    ->label('Total Earnings')
                    ->money('KES'),
                TextEntry::make('balance')
                    ->label('Current Balance')
                    ->money('KES'),
            ]);
    }

    /**
     * Fallback shown when the escort has no uploaded media.
     */
    protected static function noMediaFallback(): Section
    {
        return Section::make('No Media')
            ->description('This escort has not uploaded any photos yet.')
            ->icon(Heroicon::OutlinedPhoto)
            ->schema([
                TextEntry::make('no_media_message')
                    ->label('')
                    ->default('No media found.')
                    ->extraAttributes(['class' => 'text-gray-400 italic']),
            ])
            ->visible(fn ($record): bool => $record->resources->isEmpty());
    }
}

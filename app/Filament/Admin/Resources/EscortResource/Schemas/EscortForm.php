<?php

namespace App\Filament\Admin\Resources\EscortResource\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Form schema for the Escort create/edit flow (EscortResource).
 *
 * Composes: User Account (no password — auto-generated + emailed by observer),
 * Profile, Physical Attributes, Rates, Services, Availability, Financial
 * (read-only, edit only). Each section carries a title, description, and icon
 * and stacks at full width per AGENTS.md.
 */
class EscortForm
{
    /**
     * Main form layout — composes every section at full width.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::userAccountSection()->columnSpanFull(),
            self::profileSection()->columnSpanFull(),
            self::physicalSection()->columnSpanFull(),
            self::ratesSection()->columnSpanFull(),
            self::servicesSection()->columnSpanFull(),
            self::availabilitySection()->columnSpanFull(),
            self::financialSection()->columnSpanFull(),
        ]);
    }

    /**
     * Linked user account — names, email, and phone. Password is auto-generated
     * by UserObserver and emailed to the escort on creation.
     */
    protected static function userAccountSection(): Section
    {
        return Section::make('User Account')
            ->description('Login identity for this escort — credentials are auto-generated and emailed on creation.')
            ->icon(Heroicon::OutlinedUser)
            ->columns(2)
            ->schema([
                TextInput::make('user.first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('user.last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('user.email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique('users', 'email', ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('user.phone_number')
                    ->label('Phone')
                    ->tel()
                    ->maxLength(20),
            ]);
    }

    /**
     * Public profile — stage name, bio, verification, and visibility toggles.
     */
    protected static function profileSection(): Section
    {
        return Section::make('Profile')
            ->description('Public-facing bio, stage name, and verification state.')
            ->icon(Heroicon::OutlinedStar)
            ->columns(2)
            ->schema([
                TextInput::make('stage_name')
                    ->label('Stage Name')
                    ->maxLength(255),
                Textarea::make('bio')
                    ->label('Bio')
                    ->maxLength(5000)
                    // Long-form text reads better spanning both columns.
                    ->columnSpanFull(),
                // Verification state is owned by EscortVerificationService
                // (AGENTS.md) — surfaced here read-only so the queue actions on
                // ViewEscort are the only way to change it.
                Select::make('verification_status')
                    ->label('Verification Status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(),
                Toggle::make('is_verified')
                    ->label('Verified')
                    ->disabled()
                    ->dehydrated(),
                Toggle::make('featured')
                    ->label('Featured'),
                Toggle::make('available')
                    ->label('Available'),
                Toggle::make('accepting_new_clients')
                    ->label('Accepting New Clients'),
            ]);
    }

    /**
     * Optional appearance details shown on the public profile.
     */
    protected static function physicalSection(): Section
    {
        return Section::make('Physical Attributes')
            ->description('Optional appearance details shown on the public profile.')
            ->icon(Heroicon::OutlinedIdentification)
            ->columns(2)
            ->schema([
                TextInput::make('height')
                    ->label('Height (cm)')
                    ->numeric()
                    ->maxLength(5),
                TextInput::make('weight')
                    ->label('Weight (kg)')
                    ->numeric()
                    ->maxLength(5),
                Select::make('body_type')
                    ->label('Body Type')
                    ->options([
                        'slim' => 'Slim',
                        'athletic' => 'Athletic',
                        'average' => 'Average',
                        'curvy' => 'Curvy',
                        'muscular' => 'Muscular',
                        'stocky' => 'Stocky',
                    ]),
                Select::make('hair_color')
                    ->label('Hair Color')
                    ->options([
                        'black' => 'Black',
                        'brown' => 'Brown',
                        'blonde' => 'Blonde',
                        'red' => 'Red',
                        'gray' => 'Gray',
                        'other' => 'Other',
                    ]),
                Select::make('eye_color')
                    ->label('Eye Color')
                    ->options([
                        'brown' => 'Brown',
                        'blue' => 'Blue',
                        'green' => 'Green',
                        'hazel' => 'Hazel',
                        'gray' => 'Gray',
                        'other' => 'Other',
                    ]),
            ]);
    }

    /**
     * Hourly and nightly rates, in Kenyan Shillings.
     */
    protected static function ratesSection(): Section
    {
        return Section::make('Rates')
            ->description('What this escort charges, in Kenyan Shillings.')
            ->icon(Heroicon::OutlinedBanknotes)
            ->columns(2)
            ->schema([
                TextInput::make('rate_per_hour')
                    ->label('Rate Per Hour (KES)')
                    ->numeric()
                    ->prefix('KES'),
                TextInput::make('rate_per_night')
                    ->label('Rate Per Night (KES)')
                    ->numeric()
                    ->prefix('KES'),
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
                CheckboxList::make('services')
                    ->label('Services Offered')
                    // Options come from the global helper so the admin list stays
                    // in sync with the API's accepted service values.
                    ->options(getEscortServices())
                    ->columns(3),
                TextInput::make('working_hours')
                    ->label('Working Hours')
                    ->maxLength(255),
            ]);
    }

    /**
     * How the escort accepts clients — incall / outcall toggles.
     */
    protected static function availabilitySection(): Section
    {
        return Section::make('Availability')
            ->description('How and when this escort accepts clients.')
            ->icon(Heroicon::OutlinedClock)
            ->columns(2)
            ->schema([
                Toggle::make('incall_available')
                    ->label('Incall Available'),
                Toggle::make('outcall_available')
                    ->label('Outcall Available'),
            ]);
    }

    /**
     * Earnings and balance — read-only, and only relevant once the record exists.
     */
    protected static function financialSection(): Section
    {
        return Section::make('Financial')
            ->description('Earnings and balance — read-only, managed by the credit system.')
            ->icon(Heroicon::OutlinedWallet)
            ->columns(2)
            // Financial figures are owned by the credit system — surfaced here for
            // reference only, and only on edit (a new escort has none yet).
            ->visible(fn (string $operation): bool => $operation === 'edit')
            ->schema([
                TextInput::make('earnings')
                    ->label('Total Earnings')
                    ->numeric()
                    ->prefix('KES')
                    ->disabled(),
                TextInput::make('balance')
                    ->label('Current Balance')
                    ->numeric()
                    ->prefix('KES')
                    ->disabled(),
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\UserResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Form schema for the User create/edit flow (UserResource).
 *
 * Composes: Personal Information, Account, Access. There is no password field —
 * new staff get an auto-generated temporary password + reset email via
 * UserObserver, so the form only captures identity, status, and roles.
 * Each section carries a title, description, and icon and stacks at full width
 * per AGENTS.md.
 */
class UserForm
{
    /**
     * Main form layout — composes every section at full width.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::personalInfoSection()->columnSpanFull(),
            self::accountSection()->columnSpanFull(),
            self::accessSection()->columnSpanFull(),
        ]);
    }

    /**
     * Identity — the staff member's name and contact details.
     */
    protected static function personalInfoSection(): Section
    {
        return Section::make('Personal Information')
            ->description('Name and contact details for this staff account.')
            ->icon(Heroicon::OutlinedUser)
            ->columns(2)
            ->schema([
                TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique('users', 'email', ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('phone_number')
                    ->label('Phone')
                    ->tel()
                    ->maxLength(20),
            ]);
    }

    /**
     * Account state — whether the staff member can currently sign in.
     */
    protected static function accountSection(): Section
    {
        return Section::make('Account')
            ->description('Sign-in status. A temporary password is emailed on creation.')
            ->icon(Heroicon::OutlinedKey)
            ->schema([
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    /**
     * Access — the Spatie roles that scope what this staff member can do.
     */
    protected static function accessSection(): Section
    {
        return Section::make('Access')
            ->description('Roles granted to this staff member (Spatie + Filament Shield).')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->schema([
                Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->required(),
            ]);
    }
}

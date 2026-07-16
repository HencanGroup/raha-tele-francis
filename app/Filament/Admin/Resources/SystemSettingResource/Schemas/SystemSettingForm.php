<?php

namespace App\Filament\Admin\Resources\SystemSettingResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Form schema for the SystemSetting create/edit flow (SystemSettingResource).
 *
 * Composes a single section: Key, Type, and Value fields.
 */
class SystemSettingForm
{
    /**
     * Main form layout — composes the setting section at full width.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::settingSection()->columnSpanFull(),
        ]);
    }

    /**
     * Setting key, type selector, and value input.
     *
     * Key is disabled on edit to prevent breaking references in application
     * code. Type determines how the value is cast when loaded into config.
     */
    protected static function settingSection(): Section
    {
        return Section::make('Setting')
            ->description('Define a platform configuration variable — key, type, and value.')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->columns(2)
            ->schema([
                TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?string $operation): bool => $operation === 'edit')
                    ->dehydrated(fn (?string $operation): bool => $operation !== 'edit'),
                Select::make('type')
                    ->label('Type')
                    ->required()
                    ->options([
                        'string' => 'String',
                        'integer' => 'Integer',
                        'decimal' => 'Decimal',
                        'boolean' => 'Boolean',
                    ])
                    ->default('string'),
                Textarea::make('value')
                    ->label('Value')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}

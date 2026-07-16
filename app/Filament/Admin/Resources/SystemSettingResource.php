<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SystemSettingResource\Pages\CreateSystemSetting;
use App\Filament\Admin\Resources\SystemSettingResource\Pages\EditSystemSetting;
use App\Filament\Admin\Resources\SystemSettingResource\Pages\ListSystemSettings;
use App\Filament\Admin\Resources\SystemSettingResource\Schemas\SystemSettingForm;
use App\Filament\Admin\Resources\SystemSettingResource\Tables\SystemSettingsTable;
use App\Models\SystemSetting;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Manages platform-wide configuration variables stored as key-value pairs.
 *
 * Admins can create, edit, and delete settings. Default values are seeded
 * via SystemSettingSeeder and can be restored by re-running the seeder.
 */
class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static ?int $navigationSort = 1;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'Setting';
    }

    public static function getPluralModelLabel(): string
    {
        return 'System Settings';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configuration';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    /* ── Form (create + edit) ── */

    public static function form(Schema $schema): Schema
    {
        return SystemSettingForm::configure($schema);
    }

    /* ── Table ── */

    public static function table(Table $table): Table
    {
        return SystemSettingsTable::configure($table);
    }

    /* ── Pages ── */

    public static function getPages(): array
    {
        return [
            'index' => ListSystemSettings::route('/'),
            'create' => CreateSystemSetting::route('/create'),
            'edit' => EditSystemSetting::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\EscortMediaResource\Schemas;

use App\Models\Escort;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Form schema for the EscortMedia create/edit flow (EscortMediaResource).
 *
 * Composes: Media File (escort, type, paths, caption) and Display Settings
 * (primary, verified, public toggles, sort order).
 */
class EscortMediaForm
{
    /**
     * Main form layout — composes every section at full width.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::mediaFileSection()->columnSpanFull(),
            self::displaySettingsSection()->columnSpanFull(),
        ]);
    }

    /**
     * Media file metadata — which escort, file type, storage paths, and caption.
     */
    protected static function mediaFileSection(): Section
    {
        return Section::make(__('admin/settings.escort_media.section.file'))
            ->description(__('admin/settings.escort_media.section.file_hint'))
            ->icon(Heroicon::OutlinedPhoto)
            ->columns(2)
            ->schema([
                Select::make('escort_id')
                    ->label(__('admin/settings.escort_media.field.escort'))
                    ->relationship('escort', 'stage_name')
                    ->getOptionLabelFromRecordUsing(fn (Escort $record): string => $record->stage_name ?? "Escort #{$record->id}")
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('type')
                    ->label(__('admin/settings.escort_media.field.type'))
                    ->options([
                        'photo' => 'Photo',
                        'video' => 'Video',
                    ])
                    ->default('photo')
                    ->required(),

                TextInput::make('path')
                    ->label(__('admin/settings.escort_media.field.path'))
                    ->maxLength(512)
                    ->required(),

                TextInput::make('thumbnail_path')
                    ->label(__('admin/settings.escort_media.field.thumbnail'))
                    ->maxLength(512),

                TextInput::make('caption')
                    ->label(__('admin/settings.escort_media.field.caption'))
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Visibility and ordering — primary, verified, public toggles and sort position.
     */
    protected static function displaySettingsSection(): Section
    {
        return Section::make(__('admin/settings.escort_media.section.display'))
            ->description(__('admin/settings.escort_media.section.display_hint'))
            ->icon(Heroicon::OutlinedEye)
            ->columns(2)
            ->schema([
                Toggle::make('is_primary')
                    ->label(__('admin/settings.escort_media.field.is_primary')),
                Toggle::make('is_verified')
                    ->label(__('admin/settings.escort_media.field.is_verified')),
                Toggle::make('is_public')
                    ->label(__('admin/settings.escort_media.field.is_public')),
                TextInput::make('sort_order')
                    ->label(__('admin/settings.escort_media.field.sort_order'))
                    ->numeric()
                    ->default(0),
            ]);
    }
}

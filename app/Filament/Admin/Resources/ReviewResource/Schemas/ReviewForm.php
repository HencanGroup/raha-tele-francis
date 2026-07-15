<?php

namespace App\Filament\Admin\Resources\ReviewResource\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Form schema for the Review edit flow (ReviewResource).
 *
 * Composes: Review Details (read-only rating + comment + author info) and
 * Moderation (verification + visibility toggles).
 */
class ReviewForm
{
    /**
     * Main form layout — composes every section at full width.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::reviewDetailsSection()->columnSpanFull(),
            self::moderationSection()->columnSpanFull(),
        ]);
    }

    /**
     * Read-only review details — rating, comment, and the associated user + escort.
     */
    protected static function reviewDetailsSection(): Section
    {
        return Section::make(__('admin/settings.review.section.details'))
            ->description(__('admin/settings.review.section.details_hint'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->columns(2)
            ->schema([
                TextInput::make('user.name')
                    ->label(__('admin/settings.review.field.author'))
                    ->disabled(),
                TextInput::make('escort.stage_name')
                    ->label(__('admin/settings.review.field.escort'))
                    ->disabled(),
                TextInput::make('rating')
                    ->label(__('admin/settings.review.field.rating'))
                    ->numeric()
                    ->disabled(),
                Textarea::make('comment')
                    ->label(__('admin/settings.review.field.comment'))
                    ->columnSpanFull()
                    ->disabled(),
            ]);
    }

    /**
     * Moderation controls — verify the review and toggle its visibility.
     */
    protected static function moderationSection(): Section
    {
        return Section::make(__('admin/settings.review.section.moderation'))
            ->description(__('admin/settings.review.section.moderation_hint'))
            ->icon(Heroicon::OutlinedShieldCheck)
            ->columns(2)
            ->schema([
                Toggle::make('is_verified')
                    ->label(__('admin/settings.review.field.is_verified')),
                Toggle::make('is_visible')
                    ->label(__('admin/settings.review.field.is_visible')),
            ]);
    }
}

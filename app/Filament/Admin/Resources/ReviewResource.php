<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReviewResource\Pages\EditReview;
use App\Filament\Admin\Resources\ReviewResource\Pages\ListReviews;
use App\Filament\Admin\Resources\ReviewResource\Schemas\ReviewForm;
use App\Models\Review;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages client reviews in the admin moderation panel.
 *
 * Admins can view, verify, hide, or delete reviews. Reviews are submitted by
 * members through the API (not created in the admin panel). Moderation actions
 * (verify/hide) are handled inline via the form, and the escort's aggregate
 * rating is recalculated on every save via Escort::updateRating().
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?int $navigationSort = 1;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'Review';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Reviews';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Moderation';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    /* ── Form (edit only — reviews are created via the API) ── */

    public static function form(Schema $schema): Schema
    {
        return ReviewForm::configure($schema);
    }

    /* ── Query & Pages ── */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'escort.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }
}

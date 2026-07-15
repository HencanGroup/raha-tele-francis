<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EscortMediaResource\Pages\CreateEscortMedia;
use App\Filament\Admin\Resources\EscortMediaResource\Pages\EditEscortMedia;
use App\Filament\Admin\Resources\EscortMediaResource\Pages\ListEscortMedia;
use App\Filament\Admin\Resources\EscortMediaResource\Schemas\EscortMediaForm;
use App\Models\EscortResource;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages escort gallery media (photos/videos) in the admin moderation panel.
 *
 * Each media item belongs to an escort profile and carries verification and
 * visibility state. Admins can upload, edit, verify, or remove media. The
 * underlying model is App\Models\EscortResource — the Filament resource uses
 * "EscortMedia" as its display name to avoid the "EscortResourceResource"
 * naming collision (the EscortResource/ directory is already taken by the
 * Escort CRUD resource).
 */
class EscortMediaResource extends Resource
{
    protected static ?string $model = EscortResource::class;

    protected static ?int $navigationSort = 3;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'Escort Media';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Escort Media';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Moderation';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-photo';
    }

    /* ── Form (create + edit) ── */

    public static function form(Schema $schema): Schema
    {
        // Form fields live in the dedicated schema class (AGENTS.md split-file rule).
        return EscortMediaForm::configure($schema);
    }

    /* ── Query & Pages ── */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('escort.user');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEscortMedia::route('/'),
            'create' => CreateEscortMedia::route('/create'),
            'edit' => EditEscortMedia::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EscortResource\Pages\CreateEscort;
use App\Filament\Admin\Resources\EscortResource\Pages\EditEscort;
use App\Filament\Admin\Resources\EscortResource\Pages\ListEscorts;
use App\Filament\Admin\Resources\EscortResource\Pages\ViewEscort;
use App\Filament\Admin\Resources\EscortResource\Schemas\EscortForm;
use App\Models\Escort;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages escort profiles and their linked user accounts.
 *
 * Each escort has a 1:1 User record (user_type = 'escort') plus an Escort
 * profile record. Both are created together in a single transaction
 * (see CreateEscort::handleRecordCreation()). The navigation badge surfaces
 * the pending-application queue so moderators can spot unreviewed
 * self-registrations at a glance.
 */
class EscortResource extends Resource
{
    protected static ?string $model = Escort::class;

    protected static ?int $navigationSort = 3;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'Escort';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Escorts';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Roles & Users';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-star';
    }

    /**
     * Count of un-reviewed applications shown next to the nav item.
     *
     * Acts as the dedicated pending-approval queue affordance — the count is
     * cheap (indexed on verification_status) and refreshed per request.
     */
    public static function getNavigationBadge(): ?string
    {
        $pending = static::getEloquentQuery()
            ->where('verification_status', 'pending')
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getEloquentQuery()->where('verification_status', 'pending')->exists()
            ? 'warning'
            : 'gray';
    }

    /* ── Form (create + edit) ── */

    public static function form(Schema $schema): Schema
    {
        // Form fields live in the dedicated schema class (AGENTS.md split-file rule).
        return EscortForm::configure($schema);
    }

    /* ── Query & Pages ── */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEscorts::route('/'),
            'create' => CreateEscort::route('/create'),
            'view' => ViewEscort::route('/{record}'),
            'edit' => EditEscort::route('/{record}/edit'),
        ];
    }
}

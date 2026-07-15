<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages\CreateUser;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Filament\Admin\Resources\UserResource\Schemas\UserForm;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin → Users resource.
 *
 * Manages platform staff accounts (user_type = 'system_user') — escorts and
 * members are handled by EscortResource / MemberResource and the API, and are
 * excluded from this resource's query (see UsersTable::modifyQueryUsing()).
 * Suspend/activate/force-reset mutations delegate to UserService; this Resource
 * only wires the form, table, and pages.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    /* ── Navigation ── */

    // First entry under "Roles & Users", ahead of Members and Escorts.
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'User';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Roles & Users';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-users';
    }

    /* ── Form (create + edit) ── */

    public static function form(Schema $schema): Schema
    {
        // Form fields live in the dedicated schema class (AGENTS.md split-file rule).
        return UserForm::configure($schema);
    }

    /* ── Query & Pages ── */

    public static function getEloquentQuery(): Builder
    {
        // Eager-load roles so the Roles badge column stays off the N+1 path.
        return parent::getEloquentQuery()->with('roles');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

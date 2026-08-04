<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MemberResource\Pages\ListMembers;
use App\Filament\Admin\Resources\MemberResource\Pages\ViewMember;
use App\Models\Member;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages member profiles in the admin panel.
 *
 * This resource is **read-only** — member profiles are created through the
 * Inertia frontend API (registration, social login). Admins can view wallet
 * balances, social login associations, and account metadata.
 */
class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?int $navigationSort = 2;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'Member';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Members';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Roles & Users';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-identification';
    }

    /* ── Authorisation overrides (read-only) ── */

    /**
     * Members are created through the API, not the admin panel.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Wallet and social-login data is immutable from the admin panel.
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Member records are soft-deleted through the User resource, not here.
     */
    public static function canDelete($record): bool
    {
        return false;
    }

    /* ── Query & Pages ── */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'view' => ViewMember::route('/{record}'),
        ];
    }
}

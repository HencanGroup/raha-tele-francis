<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WithdrawalResource\Pages\ListWithdrawals;
use App\Filament\Admin\Resources\WithdrawalResource\Tables\WithdrawalsTable;
use App\Models\Withdrawal;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages escort credit withdrawals in the admin panel.
 *
 * Withdrawals are created by escorts through the API. Admins only approve or
 * refund them via row actions (delegated to WithdrawalService) — the record
 * itself is never created, edited, or hard-deleted from this panel. Follows
 * the split-file layout: this class stays thin and delegates its table to
 * Tables/WithdrawalsTable.
 */
class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?int $navigationSort = 3;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'Withdrawal';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Withdrawals';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Financial';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    /* ── Authorisation overrides (approve/refund only) ── */

    /**
     * Withdrawals originate from the escort API, not the admin panel.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Record state changes only through the approve/refund actions.
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Financial records are kept for audit — never deleted.
     */
    public static function canDelete($record): bool
    {
        return false;
    }

    /* ── Query & Pages ── */

    // Eager-load the requesting escort so identity columns avoid N+1.
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWithdrawals::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return WithdrawalsTable::configure($table);
    }
}

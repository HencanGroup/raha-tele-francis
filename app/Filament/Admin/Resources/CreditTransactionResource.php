<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CreditTransactionResource\Pages\ListCreditTransactions;
use App\Filament\Admin\Resources\CreditTransactionResource\Pages\ViewCreditTransaction;
use App\Models\CreditTransaction;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages the immutable credit transaction ledger in the admin panel.
 *
 * This resource is **read-only** — transactions are written by the credit
 * system (purchases, usage, bonuses, withdrawals). Admins can view the full
 * ledger for reconciliation and support but never create, edit, or delete
 * entries.
 */
class CreditTransactionResource extends Resource
{
    protected static ?string $model = CreditTransaction::class;

    protected static ?int $navigationSort = 1;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'Credit Transaction';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Credit Transactions';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Financial';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    /* ── Authorisation overrides (read-only) ── */

    /**
     * Transactions are immutable — written by the credit system, never the panel.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Ledger entries are the source of truth — never edited.
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Transactions are soft-deleted for audit trail preservation.
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
            'index' => ListCreditTransactions::route('/'),
            'view' => ViewCreditTransaction::route('/{record}'),
        ];
    }
}

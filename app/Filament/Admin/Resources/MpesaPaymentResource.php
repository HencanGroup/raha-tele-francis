<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MpesaPaymentResource\Pages\ListMpesaPayments;
use App\Filament\Admin\Resources\MpesaPaymentResource\Pages\ViewMpesaPayment;
use App\Models\MpesaPayment;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages M-Pesa payment records in the admin panel.
 *
 * This resource is **read-only** — payments are created by Daraja callbacks
 * (STK push responses, B2C results). Admins can view payment history for
 * reconciliation and support but never create, edit, or delete entries.
 */
class MpesaPaymentResource extends Resource
{
    protected static ?string $model = MpesaPayment::class;

    protected static ?int $navigationSort = 2;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'M-Pesa Payment';
    }

    public static function getPluralModelLabel(): string
    {
        return 'M-Pesa Payments';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Financial';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    /* ── Authorisation overrides (read-only) ── */

    /**
     * Payments are created by Daraja callbacks, not the admin panel.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Payment records are the source of truth — never edited.
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Payment records are soft-deleted for audit trail preservation.
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
            'index' => ListMpesaPayments::route('/'),
            'view' => ViewMpesaPayment::route('/{record}'),
        ];
    }
}

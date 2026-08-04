<?php

namespace App\Filament\Admin\Resources\WithdrawalResource\Pages;

use App\Filament\Admin\Resources\WithdrawalResource;
use Filament\Resources\Pages\ListRecords;

/**
 * List page for the Withdrawal resource.
 *
 * Stock ListRecords — all interactions live on the table (row actions that
 * delegate to WithdrawalService). No overrides needed.
 */
class ListWithdrawals extends ListRecords
{
    protected static string $resource = WithdrawalResource::class;
}

<?php

namespace App\Filament\Admin\Resources\CreditTransactionResource\Widgets;

use App\Models\CreditTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Monthly ledger stats shown above the credit-transactions table.
 *
 * Three cards — platform earnings, member spendings, escort earnings — each
 * summed from the immutable ledger for the current calendar month, so staff
 * can reconcile this month's 30/70 split at a glance.
 */
class CreditTransactionStatsOverview extends BaseWidget
{
    /**
     * Three cards in a single row above the table.
     */
    protected int|array|null $columns = 3;

    /**
     * Poll every 60s — monthly aggregates move slowly; sub-minute polling
     * would add query load for no benefit.
     */
    protected ?string $pollingInterval = '60s';

    /**
     * Compose the three current-month stat cards from the ledger.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        // Shared current-month window — all three cards read the same period.
        $currentMonth = CreditTransaction::query()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month);

        // The platform's 30% cut of every spend.
        $platformEarnings = (float) (clone $currentMonth)
            ->where('type', 'platform_commission')
            ->sum('amount');

        // Everything members spent (phone unlocks + paid messages).
        $memberSpendings = (float) (clone $currentMonth)
            ->where('type', 'usage')
            ->sum('amount');

        // The escorts' 70% share credited back to them.
        $escortEarnings = (float) (clone $currentMonth)
            ->where('type', 'commission')
            ->sum('amount');

        return [
            Stat::make('Platform Earnings', number_format($platformEarnings, 2).' credits')
                ->description('≈ KES '.number_format($platformEarnings * (float) config('system_settings.credit_value_kes', 5), 2).' • This month')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('gold'),

            Stat::make('Member Spendings', number_format($memberSpendings, 2).' credits')
                ->description('Spent by members • This month')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('danger'),

            Stat::make('Escort Earnings', number_format($escortEarnings, 2).' credits')
                ->description('≈ KES '.number_format($escortEarnings * (float) config('system_settings.credit_value_kes', 5), 2).' • This month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}

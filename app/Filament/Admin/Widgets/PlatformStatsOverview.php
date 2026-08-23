<?php

namespace App\Filament\Admin\Widgets;

use App\Models\CreditTransaction;
use App\Models\MpesaPayment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Platform-level statistics overview displayed at the top of the admin dashboard.
 *
 * Shows six stat cards in a 3×2 grid: total users, escorts, members, plus
 * gross revenue and platform earnings scoped to the CURRENT MONTH (all-time
 * credits issued). Polls every 30 seconds because the underlying counts
 * change slowly (new registrations, payments).
 */
class PlatformStatsOverview extends BaseWidget
{
    /**
     * Three cards per row — six stats render as two rows of three.
     */
    protected int|array|null $columns = 3;

    /**
     * Poll every 30s — counts are cheap and the dashboard is admin-only,
     * so no caching layer is needed at this scale.
     */
    protected ?string $pollingInterval = '30s';

    /**
     * Compose the six stat cards for the platform overview.
     *
     * Each card queries the DB directly via aggregate queries.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalEscorts = User::where('user_type', 'escort')->count();
        $totalMembers = User::where('user_type', 'member')->count();

        // Gross revenue for the CURRENT MONTH from completed M-Pesa payments.
        $totalRevenue = MpesaPayment::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount') ?? 0;

        // Total credits purchased via completed M-Pesa payments.
        $totalCreditsIssued = MpesaPayment::where('status', 'completed')
            ->sum('credits_awarded') ?? 0;

        // Platform commission income for the CURRENT MONTH from the immutable
        // ledger — every spend writes an explicit 'platform_commission' row
        // for the platform's cut of the 30/70 split. Converted to KES via
        // CREDIT_VALUE_KES.
        $platformEarnings = (float) CreditTransaction::where('type', 'platform_commission')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        $platformEarningsKes = $platformEarnings * (float) config('system_settings.credit_value_kes', 5);

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description('All registered accounts')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Escorts', number_format($totalEscorts))
                ->description('All escort profiles')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make('Members', number_format($totalMembers))
                ->description('Registered members')
                ->descriptionIcon('heroicon-m-user')
                ->color('success'),

            Stat::make('Revenue (KES)', 'KES '.number_format($totalRevenue, 2))
                ->description('M-Pesa payments • This month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            // Platform's own take — the 30% commission on member spends.
            Stat::make('Platform Earnings', number_format($platformEarnings, 2).' credits')
                ->description('≈ KES '.number_format($platformEarningsKes, 2).' • This month')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('gold'),

            Stat::make('Credits Issued', number_format($totalCreditsIssued))
                ->description('Via M-Pesa purchases')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('gray'),
        ];
    }
}

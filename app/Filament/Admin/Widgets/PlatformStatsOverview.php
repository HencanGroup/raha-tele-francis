<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Escort;
use App\Models\MpesaPayment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Platform-level statistics overview displayed at the top of the admin dashboard.
 *
 * Shows six stat cards: total users, total escorts, total members, total revenue,
 * credits in circulation, and pending verifications. Polls every 30 seconds because
 * the underlying counts change slowly (new registrations, payments).
 */
class PlatformStatsOverview extends BaseWidget
{
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
        $pendingVerifications = Escort::where('verification_status', 'pending')->count();

        // Revenue from completed M-Pesa payments (KES).
        $totalRevenue = MpesaPayment::where('status', 'completed')
            ->sum('amount') ?? 0;

        // Total credits purchased via completed M-Pesa payments.
        $totalCreditsIssued = MpesaPayment::where('status', 'completed')
            ->sum('credits_awarded') ?? 0;

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
                ->description('From completed M-Pesa payments')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Credits Issued', number_format($totalCreditsIssued))
                ->description('Via M-Pesa purchases')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('gray'),

            Stat::make('Pending Verifications', number_format($pendingVerifications))
                ->description('Escorts awaiting approval')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('danger'),
        ];
    }
}

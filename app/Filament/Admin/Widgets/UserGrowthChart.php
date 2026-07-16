<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

/**
 * User-growth line chart showing new registrations per month over the last 12
 * months, broken down by user_type (escort, member, system_user).
 *
 * Polls every 60 seconds — user registrations change slowly, so aggressive
 * polling is unnecessary and would add unnecessary query load.
 */
class UserGrowthChart extends ChartWidget
{
    protected ?string $heading = 'User Growth';

    /**
     * 60s interval — registrations are infrequent enough that sub-minute
     * polling provides no meaningful benefit.
     */
    protected ?string $pollingInterval = '60s';

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Build the chart datasets from monthly registration counts.
     *
     * Queries the users table grouped by month and user_type for the last
     * 12 months. Each type gets its own dataset line.
     *
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i));
        $labels = $months->map(fn ($d) => $d->format('M Y'))->toArray();

        $types = ['escort', 'member', 'system_user'];
        $colors = ['#f59e0b', '#22c55e', '#3b82f6'];
        $datasets = [];

        foreach ($types as $i => $type) {
            $data = $months->map(function ($month) use ($type) {
                return User::where('user_type', $type)
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
            })->toArray();

            $datasets[] = [
                'label' => ucfirst($type).'s',
                'data' => $data,
                'borderColor' => $colors[$i],
                'backgroundColor' => $colors[$i].'20',
                'fill' => false,
                'tension' => 0.3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }
}

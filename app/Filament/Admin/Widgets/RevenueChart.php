<?php

namespace App\Filament\Admin\Widgets;

use App\Models\MpesaPayment;
use Filament\Widgets\ChartWidget;

/**
 * Monthly revenue bar chart from completed M-Pesa payments over the last 12
 * months.
 *
 * Polls every 60 seconds — new payments arrive sporadically, so sub-minute
 * polling is unnecessary and would add unnecessary query load.
 */
class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Revenue (KES)';

    /**
     * 60s interval — payments are infrequent enough that sub-minute
     * polling provides no meaningful benefit.
     */
    protected ?string $pollingInterval = '60s';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Build the revenue dataset from completed M-Pesa payments grouped by month.
     *
     * Queries the mpesa_payments table for completed payments over the last
     * 12 months, summing the amount column per month.
     *
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i));
        $labels = $months->map(fn ($d) => $d->format('M Y'))->toArray();

        $data = $months->map(function ($month) {
            return (float) MpesaPayment::where('status', 'completed')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $data,
                    'backgroundColor' => '#f59e0b80',
                    'borderColor' => '#f59e0b',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }
}

<?php

namespace App\Filament\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared date-range filtering for Filament tables (AGENTS.md → "Filament Table Rules").
 *
 * Any Table class that exposes a date filter uses this trait and calls
 * {@see self::applyDateRangeFilter()} from inside the filter's query() callback,
 * so no Table class ever parses/clamps dates itself.
 *
 * This project has no date-range-picker package installed, so the filter form
 * uses two native DatePicker fields keyed `from` and `to` (rather than a single
 * "dd/mm/yyyy - dd/mm/yyyy" string). The trait reads those two keys, applies the
 * same bounded-window clamp, and falls back to a default lookback when empty.
 */
trait HasDateRangeFilter
{
    /**
     * Apply a from/to date-range filter to a query, clamped to a maximum window.
     *
     * @param  array  $data  Filter form state — expects `from` and/or `to` (Y-m-d)
     * @param  string  $column  Column to filter on
     * @param  int  $defaultMonths  Lookback window when no range is supplied
     * @param  int  $maxMonths  Maximum range — clamps overly wide selections
     */
    protected static function applyDateRangeFilter(
        Builder $query,
        array $data,
        string $column = 'created_at',
        int $defaultMonths = 2,
        int $maxMonths = 3,
    ): Builder {
        $from = filled($data['from'] ?? null) ? Carbon::parse($data['from'])->startOfDay() : null;
        $to = filled($data['to'] ?? null) ? Carbon::parse($data['to'])->endOfDay() : null;

        // Neither bound supplied — fall back to a sensible default lookback.
        if (! $from && ! $to) {
            return $query->where($column, '>=', now()->subMonths($defaultMonths)->startOfDay());
        }

        // Only one bound supplied — anchor the missing side to the maxMonths window
        // so a lone "from"/"to" still produces a bounded query.
        $from ??= $to->copy()->subMonths($maxMonths)->startOfDay();
        $to ??= now()->endOfDay();

        // Clamp the window to maxMonths to keep queries bounded.
        if ($from->diffInMonths($to) > $maxMonths) {
            $from = $to->copy()->subMonths($maxMonths)->startOfDay();
        }

        return $query
            ->where($column, '>=', $from)
            ->where($column, '<=', $to);
    }
}

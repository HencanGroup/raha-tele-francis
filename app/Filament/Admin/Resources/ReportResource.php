<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReportResource\Pages\ListReports;
use App\Filament\Admin\Resources\ReportResource\Pages\ViewReport;
use App\Models\Report;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages content reports in the admin moderation panel.
 *
 * This resource is **read-only** — reports are submitted by members through
 * the API. Admins can view report details and resolve or dismiss them.
 */
class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?int $navigationSort = 4;

    /* ── Navigation ── */

    public static function getModelLabel(): string
    {
        return 'Report';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Reports';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Moderation';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-flag';
    }

    /* ── Authorisation overrides (read-only) ── */

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /* ── Query & Pages ── */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['review.user', 'review.escort', 'reporter', 'resolver']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'view' => ViewReport::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the Admin → Users (staff) table.
 *
 * Columns are declared explicitly — the password hash, remember token, and
 * other credentials are never exported. Only identity, status, roles, and
 * audit timestamps staff need for reporting. Runs on the database queue
 * per AGENTS.md.
 */
class UserExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = User::class;

    /**
     * Columns written to the exported file (identity → status → roles → audit).
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('first_name')->label('First Name'),
            ExportColumn::make('last_name')->label('Last Name'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('phone_number')->label('Phone'),
            ExportColumn::make('status')->label('Status'),
            // Roles are a many-to-many relation — Filament joins the names.
            ExportColumn::make('roles.name')->label('Roles'),
            ExportColumn::make('email_verified_at')->label('Email Verified'),
            ExportColumn::make('created_at')->label('Created'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your users export is ready ('.$export->successful_rows.' rows).';
    }
}

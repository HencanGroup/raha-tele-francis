<?php

namespace App\Filament\Admin\Resources\EscortResource\Pages;

use App\Filament\Admin\Resources\EscortResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only detail view of an escort profile.
 *
 * Uses the same form schema defined in EscortForm, rendered in read-only mode
 * so admins can inspect all profile details without the edit interface.
 */
class ViewEscort extends ViewRecord
{
    protected static string $resource = EscortResource::class;
}

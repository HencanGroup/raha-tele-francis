<?php

namespace App\Filament\Admin\Resources\EscortMediaResource\Pages;

use App\Filament\Admin\Resources\EscortMediaResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Creates a new escort media item (photo/video) and attaches it to an escort profile.
 */
class CreateEscortMedia extends CreateRecord
{
    protected static string $resource = EscortMediaResource::class;
}

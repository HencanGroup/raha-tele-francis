<?php

namespace App\Filament\Admin\Resources\SystemSettingResource\Pages;

use App\Filament\Admin\Resources\SystemSettingResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page for SystemSetting — delegates form to
 * SystemSettingForm::configure() via the Resource.
 */
class CreateSystemSetting extends CreateRecord
{
    protected static string $resource = SystemSettingResource::class;
}

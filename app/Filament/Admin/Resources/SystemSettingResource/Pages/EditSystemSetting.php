<?php

namespace App\Filament\Admin\Resources\SystemSettingResource\Pages;

use App\Filament\Admin\Resources\SystemSettingResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page for SystemSetting — delegates form to
 * SystemSettingForm::configure() via the Resource.
 */
class EditSystemSetting extends EditRecord
{
    protected static string $resource = SystemSettingResource::class;
}

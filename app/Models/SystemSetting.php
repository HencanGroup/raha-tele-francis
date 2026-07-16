<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key-value store for platform-wide configuration variables.
 *
 * Admins manage these through the Filament UI (SystemSettingResource) under the
 * "Configuration" navigation group. The values are loaded into
 * config('system_settings.*') at boot via AppServiceProvider, so all application
 * code reads them through the config facade — never from the DB directly.
 *
 * Sensible defaults are defined in config/system_settings.php; DB values
 * override them when the table exists and has rows.
 */
class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    protected $casts = [
        'value' => 'string',
    ];
}

<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LoginResponse::class,
            \App\Filament\Admin\Http\Responses\LoginResponse::class,
        );
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        User::observe(UserObserver::class);

        // Load DB system settings into config at boot, overriding defaults.
        $this->loadSystemSettings();
    }

    /**
     * Read all rows from the system_settings table and merge them into
     * config('system_settings.*'), overriding the file defaults.
     *
     * Wrapped in a try/catch because the table may not exist during
     * migrations or on a fresh deploy before `php artisan migrate`.
     */
    protected function loadSystemSettings(): void
    {
        try {
            $rows = DB::table('system_settings')->get(['key', 'value']);
        } catch (\Exception) {
            return; // Table does not exist yet — use file defaults.
        }

        $settings = Config::get('system_settings', []);

        foreach ($rows as $row) {
            $settings[$row->key] = $row->value;
        }

        Config::set('system_settings', $settings);
    }
}

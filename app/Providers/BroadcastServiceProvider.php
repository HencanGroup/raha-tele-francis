<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register broadcasting routes
        Broadcast::routes([
            'middleware' => ['web', 'auth:sanctum', 'auth']
        ]);

        // Load your channel definitions
        require base_path('routes/channels.php');
    }
}

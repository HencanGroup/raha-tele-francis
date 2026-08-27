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
        // Register broadcasting routes — uses session auth (cookies), not
        // bearer tokens, so auth:sanctum must NOT be present here.
        // Pusher's JS client POSTs to /broadcasting/auth from the browser
        // with the session cookie; Sanctum's stateful guard is not needed.
        Broadcast::routes([
            'middleware' => ['web', 'auth'],
        ]);

        // Load your channel definitions
        require base_path('routes/channels.php');
    }
}

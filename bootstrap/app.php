<?php

use App\Http\Middleware\CheckConversationExistence;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

/*
|--------------------------------------------------------------------------
| Application Configuration
|--------------------------------------------------------------------------
| This file configures the Laravel application instance.
| It sets up routing, middleware, and exception handling.
*/

return Application::configure(basePath: dirname(__DIR__))

    /*
    |--------------------------------------------------------------------------
    | Routing Configuration
    |--------------------------------------------------------------------------
    | Define the main route files for the application.
    | - web.php: web routes
    | - api.php: API routes
    | - console.php: Artisan console routes
    | - health: health check endpoint
    */
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    | Register global middleware and route middleware aliases.
    */
    ->withMiddleware(function (Middleware $middleware) {

        // Append middleware to the 'web' group
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\UpdateLastSeen::class,
        ]);

        // Register route middleware aliases for convenience
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'check-conversation-existence' => CheckConversationExistence::class,
        ]);
    })

    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    | Customize exception handling if needed.
    */
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })

    ->create();
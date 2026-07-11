<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustResetPasswordMiddleware
{
    public function handle(Request $request, Closure $next): RedirectResponse|Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        // Skip if already on the reset password page or related auth pages
        if ($request->is('admin-panel/must-reset-password') || $request->routeIs('must-reset-password')) {
            return $next($request);
        }

        // Skip if on logout route to prevent redirect loop
        if ($request->routeIs('filament.admin-panel.auth.logout')) {
            return $next($request);
        }

        // Skip for any auth-related routes (login, register, password reset, etc.)
        if ($request->routeIs('filament.admin-panel.auth.*')) {
            return $next($request);
        }

        // Redirect if user has temporary password
        if (auth()->user()->is_temp_password) {
            return redirect()->to('/admin-panel/must-reset-password');
        }

        return $next($request);
    }
}

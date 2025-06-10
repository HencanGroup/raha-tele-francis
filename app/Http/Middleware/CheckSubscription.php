<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip for guests or if user is banned/suspended
        if (!$user || $user->isBanned() || $user->status === \App\Models\User::STATUS_SUSPENDED) {
            return $next($request);
        }

        // Check if route is exempt from subscription check
        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        // Redirect if user doesn't have active subscription
        if (!$user->hasActiveSubscription()) {
            return redirect()->route('plan.index')->with('error', 'You need an active subscription to access this feature.');
        }

        return $next($request);
    }

    /**
     * Check if the current route should be exempt from subscription check
     */
    protected function isExemptRoute(Request $request): bool
    {
        $exemptRoutes = [
            'subscription.*',
            'mpesa.*',
        ];

        foreach ($exemptRoutes as $route) {
            if ($request->routeIs($route)) {
                return true;
            }
        }

        return false;
    }
}

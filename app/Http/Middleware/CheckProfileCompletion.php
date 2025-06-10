<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckProfileCompletion
{
    /**
     * List of routes that should be excluded from profile completion check
     */
    protected array $excludedRoutes = [
        'profile.update',
        'profile.edit',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If there's no authenticated user, proceed
        if (!$user) {
            return $next($request);
        }

        // Check if the current route should be excluded
        if ($this->shouldExcludeRoute($request)) {
            return $next($request);
        }

        // Get missing fields from the profile
        $missingFields = $this->getMissingFields($user);

        // If there are missing fields, redirect with a warning message
        if (!empty($missingFields)) {
            $message = 'Please complete your profile. Missing fields: ' . implode(', ', $missingFields);

            Log::warning('Profile incomplete for user: ' . $user->id, [
                'missing_fields' => $missingFields,
                'current_route' => $request->route()?->getName()
            ]);

            return redirect()->route('profile.edit', $user->id)
                ->with('warning', $message);
        }

        return $next($request);
    }

    /**
     * Check if the current route should be excluded from profile completion check
     */
    protected function shouldExcludeRoute(Request $request): bool
    {
        $currentRoute = $request->route()?->getName();

        return in_array($currentRoute, $this->excludedRoutes, true);
    }

    /**
     * Get a list of missing profile fields with proper validation
     */
    protected function getMissingFields($user): array
    {
        $fieldValidations = [
            'name' => fn($v) => !empty(trim($v ?? '')),
            'email' => function ($v) {
                return !empty(trim($v ?? '')) && filter_var($v, FILTER_VALIDATE_EMAIL);
            },
            'gender' => fn($v) => !empty($v),
            'searching_for' => fn($v) => !empty($v),
            'birth_date' => fn($v) => !empty($v),
            'profile_picture' => fn($v) => !empty($v),
            'location' => fn($v) => !empty(trim($v ?? '')),
            'bio' => fn($v) => !empty(trim($v ?? '')) && strlen(trim($v)) >= 30,
        ];

        $missing = [];

        foreach ($fieldValidations as $field => $validation) {
            $value = $user->$field ?? null;

            if (!$validation($value)) {
                $missing[] = $this->formatFieldName($field);

                Log::debug('Profile field validation failed', [
                    'field' => $field,
                    'value' => $value,
                    'user_id' => $user->id
                ]);
            }
        }

        return $missing;
    }

    /**
     * Format field name for display
     */
    protected function formatFieldName(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }
}

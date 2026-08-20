<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()
                    ? $request->user()->load('roles.permissions', 'permissions')
                    : null,
                // The current user's public profile link target — profile routes
                // bind by escort/member id, which differs from the user id.
                'user_profile' => function () use ($request): ?array {
                    $user = $request->user();

                    if (! $user) {
                        return null;
                    }

                    if ($user->isEscort()) {
                        return ['type' => 'escort', 'id' => $user->escortProfile?->id];
                    }

                    if ($user->isMember()) {
                        return ['type' => 'member', 'id' => $user->memberProfile?->id];
                    }

                    return null;
                },
            ],
            'system_variables' => [
                'phone_unlock_cost' => config('services.system_variables.phone_unlock_cost'),
                'message_cost' => config('services.system_variables.message_cost'),

            ],
            'escortServices' => getEscortServices(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}

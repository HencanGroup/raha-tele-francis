<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Session bridge — logs a Sanctum-authenticated user into the web session.
 *
 * The Inertia app authenticates through the JSON API (`/api/auth/*`), which
 * returns a Sanctum Bearer token. The rest of the app (web.php routes,
 * `usePage().props.auth`) relies on the session guard, so after a successful
 * API login the frontend calls this endpoint with the bearer token to swap its
 * token authentication into a real web session.
 */
class SessionBridgeController extends Controller
{
    /**
     * Establish a web session for the user owning the supplied Sanctum token.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $bearer = $request->bearerToken();
        $accessToken = $bearer ? PersonalAccessToken::findToken($bearer) : null;

        if (! $accessToken || ! $accessToken->tokenable instanceof User) {
            return response()->json(['message' => 'Invalid or missing token.'], 401);
        }

        $user = $accessToken->tokenable;

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json(['message' => 'Session established.']);
    }
}

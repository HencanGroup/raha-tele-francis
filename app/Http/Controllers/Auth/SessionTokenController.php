<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Issues a Sanctum token to the current session-authenticated user.
 *
 * The API (`/api/*` routes, auth:sanctum) requires Bearer tokens, but the
 * Inertia app authenticates via the session. This endpoint lets a
 * session-authenticated user obtain a fresh token for API calls without
 * re-entering credentials (used by the frontend's `ensureSessionToken`).
 */
class SessionTokenController extends Controller
{
    /**
     * Create a Sanctum token for the logged-in session user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = $user->createToken('session-api')->plainTextToken;

        return response()->json(['token' => $token]);
    }
}

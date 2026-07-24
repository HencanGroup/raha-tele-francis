<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * API authentication controller for email/password login.
 *
 * Handles login with 2FA support — if the user has 2FA enabled,
 * the endpoint returns a temporary token instead of a Sanctum token,
 * prompting the client to complete the challenge via TwoFactorAuthController.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorService,
    ) {}

    /**
     * Authenticate with email and password.
     *
     * If 2FA is enabled for the user, returns a temporary token
     * instead of a Sanctum token. The client must then call
     * POST /api/auth/2fa/verify to complete authentication.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // If 2FA is enabled, issue a temporary login token instead
        if ($this->twoFactorService->isEnabled($user)) {
            $token = $this->twoFactorService->storeLoginToken($user);

            Auth::logout();

            return response()->json([
                'two_factor_required' => true,
                'two_factor_token' => $token,
            ]);
        }

        $sanctumToken = $user->createToken('api-login')->plainTextToken;

        return response()->json([
            'token' => $sanctumToken,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Revoke the current Sanctum token.
     */
    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Format user data for the login response.
     *
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'display_name' => $user->display_name,
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }
}

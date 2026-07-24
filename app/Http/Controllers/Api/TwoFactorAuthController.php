<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TwoFactorConfirmRequest;
use App\Http\Requests\Api\TwoFactorDisableRequest;
use App\Http\Requests\Api\TwoFactorEnableRequest;
use App\Http\Requests\Api\TwoFactorRecoveryRequest;
use App\Http\Requests\Api\TwoFactorVerifyRequest;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * API 2FA management and challenge controller.
 *
 * Split into two groups:
 *   1. Management (auth:sanctum) — status, enable, confirm, disable
 *   2. Challenge (public, via two_factor_token) — verify, recovery
 */
class TwoFactorAuthController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorService,
    ) {}

    /**
     * Check whether 2FA is enabled for the authenticated user.
     *
     * GET /api/auth/2fa/status
     */
    public function status(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'enabled' => $this->twoFactorService->isEnabled($user),
            'confirmed_at' => $user->two_factor_confirmed_at?->toISOString(),
        ]);
    }

    /**
     * Enable 2FA — generates a secret, QR code URL, and recovery codes.
     *
     * POST /api/auth/2fa/enable
     *
     * The secret is stored immediately but NOT confirmed until the user
     * calls /confirm with a valid TOTP code.
     */
    public function enable(TwoFactorEnableRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($this->twoFactorService->isEnabled($user)) {
            return response()->json(['message' => '2FA is already enabled.'], 409);
        }

        $setupData = $this->twoFactorService->generateSetupData($user);

        return response()->json([
            'secret' => $setupData['secret'],
            'qr_code_url' => $setupData['qr_code_url'],
            'recovery_codes' => $setupData['recovery_codes'],
        ]);
    }

    /**
     * Confirm 2FA setup by verifying a TOTP code.
     *
     * POST /api/auth/2fa/confirm
     *
     * The user must have called /enable first to generate a secret.
     * This step confirms they can generate valid codes from their
     * authenticator app.
     */
    public function confirm(TwoFactorConfirmRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => 'No 2FA secret found. Call /enable first.'], 400);
        }

        if ($this->twoFactorService->isEnabled($user)) {
            return response()->json(['message' => '2FA is already confirmed.'], 409);
        }

        if (! $this->twoFactorService->verifyCode($user, $request->input('code'))) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        $this->twoFactorService->confirmSetup($user);

        return response()->json(['message' => '2FA enabled successfully.']);
    }

    /**
     * Disable 2FA — requires the current password and a valid TOTP code.
     *
     * POST /api/auth/2fa/disable
     */
    public function disable(TwoFactorDisableRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->twoFactorService->isEnabled($user)) {
            return response()->json(['message' => '2FA is not enabled.'], 409);
        }

        if (! $this->twoFactorService->verifyCode($user, $request->input('code'))) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        $this->twoFactorService->disable($user);

        return response()->json(['message' => '2FA disabled successfully.']);
    }

    /**
     * Complete a 2FA challenge during login by verifying a TOTP code.
     *
     * POST /api/auth/2fa/verify
     *
     * Requires the two_factor_token returned by POST /api/auth/login
     * and a 6-digit TOTP code from the user's authenticator app.
     */
    public function verify(TwoFactorVerifyRequest $request): JsonResponse
    {
        $userId = $this->twoFactorService->getUserFromToken(
            $request->input('two_factor_token')
        );

        if (! $userId) {
            return response()->json(['message' => 'Invalid or expired 2FA token.'], 401);
        }

        /** @var User $user */
        $user = User::find($userId);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (! $this->twoFactorService->verifyCode($user, $request->input('code'))) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        $this->twoFactorService->consumeLoginToken($request->input('two_factor_token'));

        $sanctumToken = $user->createToken('api-login')->plainTextToken;

        return response()->json([
            'token' => $sanctumToken,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Bypass 2FA using a recovery code.
     *
     * POST /api/auth/2fa/recovery
     *
     * The recovery code is consumed (removed from the stored list).
     * If all recovery codes are exhausted, admin intervention may
     * be needed to regain access.
     */
    public function recovery(TwoFactorRecoveryRequest $request): JsonResponse
    {
        $userId = $this->twoFactorService->getUserFromToken(
            $request->input('two_factor_token')
        );

        if (! $userId) {
            return response()->json(['message' => 'Invalid or expired 2FA token.'], 401);
        }

        /** @var User $user */
        $user = User::find($userId);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (! $this->twoFactorService->verifyRecoveryCode($user, $request->input('recovery_code'))) {
            return response()->json(['message' => 'Invalid recovery code.'], 422);
        }

        $this->twoFactorService->consumeLoginToken($request->input('two_factor_token'));

        $sanctumToken = $user->createToken('api-login')->plainTextToken;

        return response()->json([
            'token' => $sanctumToken,
            'recovery_code_consumed' => true,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
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

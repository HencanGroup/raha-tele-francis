<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;

/**
 * TOTP-based two-factor authentication using the pragmarx/google2fa library.
 *
 * Handles secret generation, code verification, QR code URL generation,
 * recovery code management, and the two-factor login handshake token.
 */
class TwoFactorAuthService
{
    private const TWO_FACTER_TOKEN_PREFIX = '2fa_token_';

    private const TWO_FACTOR_TOKEN_TTL = 10;

    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(
        private readonly Google2FA $google2fa,
    ) {}

    /**
     * Generate a new TOTP secret and QR code for the user.
     *
     * The secret is encrypted before storage. Returns the raw secret and an
     * inline QR code (SVG data URI) so the frontend can display them during
     * setup. The QR is rendered locally via pragmarx/google2fa-qrcode —
     * the legacy Google Chart endpoint it replaced is deprecated and blank.
     *
     * @return array{secret: string, qr_code_url: string, recovery_codes: array}
     */
    public function generateSetupData(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $qrCodeUrl = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret,
            200,
        );

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Verify a TOTP code against the user's stored secret.
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Confirm 2FA setup by marking it as confirmed.
     */
    public function confirmSetup(User $user): void
    {
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
    }

    /**
     * Disable 2FA — clear all 2FA data.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Check if the user has 2FA fully enabled (confirmed).
     */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null;
    }

    /**
     * Verify a recovery code. If valid, remove it from the stored list.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        $index = array_search($code, $codes, true);

        if ($index === false) {
            return false;
        }

        array_splice($codes, $index, 1);

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();

        return true;
    }

    /**
     * Store a temporary login token for the two-factor challenge flow.
     *
     * Returns the token string that the client sends back to verify.
     */
    public function storeLoginToken(User $user): string
    {
        $token = Str::random(40);

        Cache::put(
            self::TWO_FACTER_TOKEN_PREFIX.$token,
            $user->id,
            now()->addMinutes(self::TWO_FACTOR_TOKEN_TTL),
        );

        return $token;
    }

    /**
     * Retrieve the user ID from a temporary login token.
     *
     * Returns null if the token is invalid or expired.
     */
    public function getUserFromToken(string $token): ?int
    {
        return Cache::get(self::TWO_FACTER_TOKEN_PREFIX.$token);
    }

    /**
     * Consume (delete) a temporary login token so it cannot be reused.
     */
    public function consumeLoginToken(string $token): void
    {
        Cache::forget(self::TWO_FACTER_TOKEN_PREFIX.$token);
    }

    /**
     * Generate an array of recovery codes in XXXX-XXXX format.
     *
     * @return array<int, string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $codes[] = strtoupper(
                implode('-', [
                    Str::random(4),
                    Str::random(4),
                ])
            );
        }

        return $codes;
    }
}

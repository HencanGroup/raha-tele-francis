<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\MpesaPayment;
use App\Models\Withdrawal;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MpesaService
{
    protected string $certificatePath;

    public function __construct()
    {
        $this->certificatePath = storage_path('app/public/cert/ProductionCertificate.cer');
    }

    /* -----------------------------------------------------------------
     | API BASE & OAUTH TOKEN (shared with the B2C payout flow)
     |-----------------------------------------------------------------*/

    /**
     * Daraja API base URL — sandbox vs production by env.
     */
    public function baseUrl(): string
    {
        return config('services.mpesa.env') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Fetch a short-lived OAuth access token from Daraja.
     *
     * @throws \RuntimeException When Daraja rejects the consumer credentials.
     */
    public function generateToken(): string
    {
        $consumerKey = config('services.mpesa.consumer_key');
        $consumerSecret = config('services.mpesa.consumer_secret');
        $credentials = base64_encode("{$consumerKey}:{$consumerSecret}");

        $response = Http::withHeaders([
            'Authorization' => "Basic {$credentials}",
        ])->get("{$this->baseUrl()}/oauth/v1/generate", [
            'grant_type' => 'client_credentials',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to generate M-Pesa token');
        }

        return $response->json('access_token');
    }

    /* -----------------------------------------------------------------
     | SECURITY CREDENTIAL (B2C / B2B)
     |-----------------------------------------------------------------*/
    public function generateCredential(string $initiatorPassword): string
    {
        $cert = file_get_contents($this->certificatePath);
        if (! $cert) {
            throw new Exception("Unable to read certificate file at: {$this->certificatePath}");
        }

        $publicKey = openssl_pkey_get_public($cert);
        if (! $publicKey) {
            throw new Exception('Invalid public key in certificate.');
        }

        $encrypted = '';
        $success = openssl_public_encrypt(
            $initiatorPassword,
            $encrypted,
            $publicKey,
            OPENSSL_PKCS1_PADDING
        );

        if (! $success) {
            throw new Exception('Failed to encrypt initiator password.');
        }

        openssl_free_key($publicKey);

        return base64_encode($encrypted);
    }

    /* -----------------------------------------------------------------
     | B2C PAYOUT (escort withdrawals)
     |-----------------------------------------------------------------*/

    /**
     * Submit an M-Pesa B2C payment request for an approved withdrawal.
     *
     * Uses the b2c_shortcode as the paying account, the security credential
     * derived from the initiator password, and embeds the withdrawal in the
     * Remarks. Returns the Daraja response so the caller can store the
     * OriginatorConversationID for callback correlation.
     *
     * @return array<string, mixed>
     */
    public function sendB2CPayout(Withdrawal $withdrawal): array
    {
        $payload = [
            'InitiatorName' => config('services.mpesa.initiator_name'),
            'SecurityCredential' => $this->generateCredential(config('services.mpesa.initiator_password')),
            'CommandID' => config('services.mpesa.b2c_command_id', 'BusinessPayment'),
            'Amount' => (int) round((float) $withdrawal->amount_kes),
            'PartyA' => config('services.mpesa.b2c_shortcode'),
            'PartyB' => $withdrawal->phone_number,
            'Remarks' => 'Escort withdrawal #'.$withdrawal->id,
            'QueueTimeOutURL' => config('services.mpesa.queue_timeout_url'),
            'ResultURL' => config('services.mpesa.result_url'),
            'Occasion' => 'WITHDRAWAL-'.$withdrawal->id,
        ];

        $response = Http::withToken($this->generateToken())
            ->timeout(30)
            ->post("{$this->baseUrl()}/mpesa/b2c/v1/paymentrequest", $payload);

        if (! $response->successful()) {
            throw new \RuntimeException($response->json('errorMessage') ?? 'B2C payment request failed');
        }

        return $response->json();
    }

    /* -----------------------------------------------------------------
     | CREDIT AWARDING (STK / C2B)
     |-----------------------------------------------------------------*/
    public function awardCredits(MpesaPayment $payment): void
    {
        DB::transaction(function () use ($payment) {

            // 🔒 Idempotency: prevent double crediting
            if ($payment->creditTransaction) {
                return;
            }

            $user = $payment->user;

            if (! $user) {
                throw new Exception('Payment user not found');
            }

            $member = $user->memberProfile;

            if (! $member) {
                throw new Exception('Member profile not found');
            }

            $creditsAwarded = (float) $payment->credits_awarded;

            if ($creditsAwarded <= 0) {
                throw new Exception('Invalid credits amount');
            }

            $balanceBefore = (float) ($member->credits ?? 0);
            $balanceAfter = $balanceBefore + $creditsAwarded;

            // ✅ Compute the expiry window — extend an unexpired window, otherwise
            // open a fresh one from today per CREDIT_EXPIRY_DAYS.
            $expiryDays = (int) config('system_settings.credit_expiry_days', 365);
            $newExpiry = now()->addDays($expiryDays);
            $currentExpiry = $member->credits_expire_at;
            $creditsExpireAt = $currentExpiry && $currentExpiry->gt(now())
                ? $currentExpiry->max($newExpiry)
                : $newExpiry;

            // ✅ Update member wallet
            $member->update([
                'credits' => $balanceAfter,
                'total_credits_earned' => (float) ($member->total_credits_earned ?? 0) + $creditsAwarded,
                'last_credit_purchase_at' => now(),
                'credits_expire_at' => $creditsExpireAt,
            ]);

            // ✅ Ledger entry
            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'purchase',
                'amount' => $creditsAwarded,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => MpesaPayment::class,
                'reference_id' => $payment->id,
                'description' => 'M-Pesa credit purchase',
                'metadata' => [
                    'mpesa_receipt' => $payment->transaction_id,
                    'phone' => $payment->phone_number,
                ],
            ]);
        });
    }
}

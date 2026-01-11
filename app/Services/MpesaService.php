<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\MpesaPayment;
use Exception;
use Illuminate\Support\Facades\DB;

class MpesaService
{
    protected string $certificatePath;

    public function __construct()
    {
        $this->certificatePath = storage_path('app/public/cert/ProductionCertificate.cer');
    }

    /* -----------------------------------------------------------------
     | SECURITY CREDENTIAL (B2C / B2B)
     |-----------------------------------------------------------------*/
    public function generateCredential(string $initiatorPassword): string
    {
        $cert = file_get_contents($this->certificatePath);
        if (!$cert) {
            throw new Exception("Unable to read certificate file at: {$this->certificatePath}");
        }

        $publicKey = openssl_pkey_get_public($cert);
        if (!$publicKey) {
            throw new Exception("Invalid public key in certificate.");
        }

        $encrypted = '';
        $success = openssl_public_encrypt(
            $initiatorPassword,
            $encrypted,
            $publicKey,
            OPENSSL_PKCS1_PADDING
        );

        if (!$success) {
            throw new Exception("Failed to encrypt initiator password.");
        }

        openssl_free_key($publicKey);

        return base64_encode($encrypted);
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

            if (!$user) {
                throw new Exception('Payment user not found');
            }

            $creditsAwarded = (float) $payment->credits_awarded;

            if ($creditsAwarded <= 0) {
                throw new Exception('Invalid credits amount');
            }

            $balanceBefore = (float) ($user->credits ?? 0);
            $balanceAfter = $balanceBefore + $creditsAwarded;

            // ✅ Update user wallet
            $user->update([
                'credits' => $balanceAfter,
                'total_credits_earned' => (float) ($user->total_credits_earned ?? 0) + $creditsAwarded,
                'last_credit_purchase_at' => now(),
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

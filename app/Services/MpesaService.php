<?php

namespace App\Services;

use Exception;

class MpesaService
{
    protected string $certificatePath;

    public function __construct()
    {
        $this->certificatePath = storage_path('app/public/cert/ProductionCertificate.cer');
    }

    public function generateCredential(string $initiatorPassword): string
    {
        // Load the certificate
        $cert = file_get_contents($this->certificatePath);
        if (!$cert) {
            throw new Exception("Unable to read certificate file at: {$this->certificatePath}");
        }

        // Extract the public key
        $publicKey = openssl_pkey_get_public($cert);
        if (!$publicKey) {
            throw new Exception("Invalid public key in certificate.");
        }

        // Encrypt the password
        $encrypted = '';
        $success = openssl_public_encrypt($initiatorPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        if (!$success) {
            throw new Exception("Failed to encrypt initiator password.");
        }

        openssl_free_key($publicKey);

        return base64_encode($encrypted);
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for using a recovery code to bypass 2FA during login.
 */
class TwoFactorRecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'two_factor_token' => ['required', 'string', 'size:40'],
            'recovery_code' => ['required', 'string'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Validation for enabling 2FA — requires the user's current password.
 */
class TwoFactorEnableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! Hash::check($value, Auth::user()->password)) {
                    $fail('The password is incorrect.');
                }
            }],
        ];
    }
}

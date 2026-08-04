<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for the escort withdrawal request endpoint.
 *
 * The amount is validated as a positive number; business rules (minimum
 * withdrawal, sufficient balance) are enforced by WithdrawalService so the
 * request stays a pure transport layer.
 */
class StoreWithdrawalRequest extends FormRequest
{
    /**
     * Only authenticated escorts may request a withdrawal.
     */
    public function authorize(): bool
    {
        return $this->user()?->isEscort() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'phone_number' => ['required', 'regex:/^2547\d{8}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The withdrawal amount is required.',
            'amount.numeric' => 'The withdrawal amount must be a number.',
            'amount.min' => 'The withdrawal amount must be greater than zero.',
            'phone_number.required' => 'The M-Pesa phone number is required.',
            'phone_number.regex' => 'The phone number must be in 2547XXXXXXXX format.',
        ];
    }
}

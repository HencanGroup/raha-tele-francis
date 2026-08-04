<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for the public escort self-registration endpoint.
 *
 * The request is intentionally public — anyone may open an application. The
 * resulting profile is created with verification_status = 'pending' and must
 * be approved by an admin before the escort can be found by clients. Business
 * rules (role assignment, pending profile creation) live in
 * EscortRegistrationService, keeping this class a pure transport layer.
 */
class StoreEscortRegistrationRequest extends FormRequest
{
    /**
     * Public endpoint — no authentication required.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['required', 'string', 'max:20'],
            'stage_name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'county_id' => ['nullable', 'integer', 'exists:counties,id'],
            'town_id' => ['nullable', 'integer', 'exists:towns,id'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'max:255'],
            'rate_per_hour' => ['nullable', 'numeric', 'min:0'],
            'rate_per_night' => ['nullable', 'numeric', 'min:0'],
            'incall_available' => ['nullable', 'boolean'],
            'outcall_available' => ['nullable', 'boolean'],
            'available' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists.',
            'password.confirmed' => 'The password confirmation does not match.',
            'stage_name.required' => 'Please provide a stage name for your profile.',
        ];
    }
}

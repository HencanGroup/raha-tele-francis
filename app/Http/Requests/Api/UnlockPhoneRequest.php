<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate the phone unlock request — requires a valid escort_id.
 */
class UnlockPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'escort_id' => ['required', 'integer', 'exists:escorts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'escort_id.required' => 'The escort ID is required.',
            'escort_id.exists' => 'The specified escort does not exist.',
        ];
    }
}

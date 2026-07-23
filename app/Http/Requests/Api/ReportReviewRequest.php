<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for reporting a review.
 */
class ReportReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for the report.',
            'reason.max' => 'Reason must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 2000 characters.',
        ];
    }
}

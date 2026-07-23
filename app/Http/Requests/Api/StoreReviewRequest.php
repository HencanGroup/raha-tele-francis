<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for creating a new review.
 */
class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'escort_id' => ['required', 'integer', 'exists:escorts,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'escort_id.required' => 'The escort ID is required.',
            'escort_id.exists' => 'The specified escort does not exist.',
            'rating.required' => 'Please provide a rating.',
            'rating.min' => 'Rating must be at least 1.',
            'rating.max' => 'Rating must not exceed 5.',
            'comment.required' => 'Please provide a comment.',
            'comment.max' => 'Comment must not exceed 1000 characters.',
        ];
    }
}

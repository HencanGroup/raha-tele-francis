<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'gender' => ['required', 'in:male,female,other'],
            'searching_for' => ['required', 'in:male,female,both'],
            'birth_date' => ['required', 'date', 'before:-18 years'],
            'bio' => ['required', 'string', 'min:50', 'max:1000'],
            'location' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'is_escort' => ['required', 'boolean'],
            'profile_picture' => [
                'nullable',
                File::image()
                    ->max(5 * 1024) // 5MB
            ],
            'gallery' => [
                'nullable',
                'array',
                'max:6',
            ],
            'gallery.*' => [
                File::image()
                    ->max(5 * 1024) // 5MB
            ],
            'verification_documents' => [
                'nullable',
                'array',
                'max:3',
            ],
            'verification_documents.*' => [
                File::types(['pdf', 'jpg', 'jpeg', 'png'])
                    ->max(10 * 1024), // 10MB
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert string 'true'/'false' to actual boolean values
        $this->merge([
            'is_escort' => filter_var($this->is_escort, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'birth_date.before' => 'You must be at least 18 years old.',
            'gallery.max' => 'You can upload maximum 6 gallery images.',
            'verification_documents.max' => 'You can upload maximum 3 documents.',
        ];
    }
}

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
            'profile_picture' => [
                'nullable',
                File::image()
                    ->max(5 * 1024) // 5MB
                    // ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000)),
            ],
            'gallery' => [
                'nullable',
                'array',
                'max:6',
            ],
            'gallery.*' => [
                File::image()
                    ->max(5 * 1024) // 5MB
                    // ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000)),
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

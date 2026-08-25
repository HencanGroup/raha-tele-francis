<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for the escort media upload endpoint.
 *
 * Accepts a file (photo or video) with optional caption and visibility.
 * The file type is auto-detected from MIME; the `type` field is not
 * user-supplied.
 */
class StoreMediaRequest extends FormRequest
{
    /**
     * Only authenticated escorts may upload media.
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
            'file' => [
                'required',
                'file',
                'max:30720', // 30 MB
                'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv,webm,mpeg,quicktime',
            ],
            'caption' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The file must be no larger than 30 MB.',
            'file.mimes' => 'The file must be a valid image (JPG, PNG, GIF, WebP) or video (MP4, MOV, AVI, MKV, WebM).',
        ];
    }
}

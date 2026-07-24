<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for the API chat send-message endpoint.
 *
 * Accepts both plain JSON messages and multipart uploads with
 * an optional file attachment (image, video, audio, or document).
 */
class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'message' => ['required_without:attachment', 'string', 'nullable'],
            'type' => ['sometimes', 'string', 'in:text,image,video,audio,file'],
            'client_id' => ['sometimes', 'string'],
            'reply_to_id' => ['sometimes', 'integer', 'exists:messages,id'],
            'requires_credit' => ['sometimes', 'boolean'],
            'credit_cost' => ['sometimes', 'numeric', 'min:0'],
            'attachment' => ['sometimes', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,mp4,mp3,ogg,pdf,doc,docx'],
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.required' => 'The conversation ID is required.',
            'conversation_id.exists' => 'The conversation does not exist.',
            'message.required_without' => 'The message field is required when there is no attachment.',
            'attachment.max' => 'The attachment must not be larger than 10MB.',
            'attachment.mimes' => 'The attachment must be a file of type: jpg, png, gif, webp, mp4, mp3, ogg, pdf, doc, docx.',
        ];
    }
}

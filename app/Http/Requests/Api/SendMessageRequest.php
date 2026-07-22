<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for the API chat send-message endpoint.
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
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.required' => 'The conversation ID is required.',
            'conversation_id.exists' => 'The conversation does not exist.',
            'message.required_without' => 'The message field is required when there is no attachment.',
        ];
    }
}

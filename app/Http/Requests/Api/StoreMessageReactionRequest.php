<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for adding/removing a reaction on a message.
 */
class StoreMessageReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reaction' => ['required', 'string', 'max:50'],
        ];
    }
}

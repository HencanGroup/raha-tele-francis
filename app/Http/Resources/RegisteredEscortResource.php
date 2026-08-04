<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a newly registered escort's user + profile into a JSON response.
 *
 * Used by POST /api/escort/register. Exposes only identity and application
 * state — never passwords, tokens, or private verification documents. The
 * verification_status tells the frontend the application is awaiting admin
 * approval.
 */
class RegisteredEscortResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $escort = $this->escortProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'user_type' => $this->user_type,
            'stage_name' => $escort?->stage_name,
            'verification_status' => $escort?->verification_status ?? 'pending',
            'is_verified' => (bool) ($escort?->is_verified ?? false),
        ];
    }
}

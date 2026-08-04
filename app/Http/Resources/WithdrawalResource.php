<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a Withdrawal into the JSON shape consumed by the Inertia
 * frontend's earnings/withdrawal screens.
 *
 * The recipient phone is obfuscated for display; the full number is never
 * sent back to the client beyond the request that created it.
 */
class WithdrawalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'amount_kes' => (float) $this->amount_kes,
            'phone_number' => $this->phone_number ? obfuscatePhone($this->phone_number) : null,
            'status' => $this->status,
            'transaction_id' => $this->transaction_id,
            'failure_reason' => $this->failure_reason,
            'created_at' => $this->created_at->toISOString(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}

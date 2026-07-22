<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a CreditTransaction ledger row into a structured JSON response.
 *
 * Used by the paginated transaction history endpoint at GET /api/earnings/transactions,
 * consumed by the Next.js frontend's earnings dashboard.
 */
class CreditTransactionResource extends JsonResource
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
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'created_at' => $this->created_at->toISOString(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}

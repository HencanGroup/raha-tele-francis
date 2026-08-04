<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms an Escort's earnings data into a structured JSON response.
 *
 * Wraps lifetime earnings, current withdrawable balance, and computed
 * summary fields. Consumed by the Inertia frontend's earnings dashboard
 * at GET /api/earnings.
 */
class EarningsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_earnings' => (float) $this->earnings,
            'current_balance' => (float) $this->balance,
            'currency' => 'KES',
            'escort_id' => $this->id,
            'stage_name' => $this->stage_name,
            'is_verified' => $this->is_verified,
        ];
    }
}

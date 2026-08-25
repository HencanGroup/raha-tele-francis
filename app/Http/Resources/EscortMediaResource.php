<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms an EscortResource (media item) into a structured JSON response.
 *
 * Includes the computed `url` and `thumbnail_url` accessors so the frontend
 * never needs to resolve storage paths directly.
 */
class EscortMediaResource extends JsonResource
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
            'escort_id' => $this->escort_id,
            'type' => $this->type,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'path' => $this->path,
            'caption' => $this->caption,
            'is_primary' => $this->is_primary,
            'is_verified' => $this->is_verified,
            'is_public' => $this->is_public,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at->toISOString(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a Review into a structured JSON response.
 *
 * Returns the review text, rating, moderation flags, the author's
 * basic profile, and timestamp. Consumed by the Next.js frontend's
 * escort profile and review-list views.
 */
class ReviewResource extends JsonResource
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
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_verified' => $this->is_verified,
            'is_visible' => $this->is_visible,
            'author' => [
                'id' => $this->user?->id,
                'first_name' => $this->user?->first_name,
                'display_name' => $this->user?->display_name,
            ],
            'escort' => [
                'id' => $this->escort?->id,
                'stage_name' => $this->escort?->stage_name,
            ],
            'created_at' => $this->created_at->toISOString(),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

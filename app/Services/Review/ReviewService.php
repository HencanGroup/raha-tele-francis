<?php

namespace App\Services\Review;

use App\Models\Escort;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business logic for the review system.
 *
 * Handles creation, update, soft-deletion, and report submission.
 * Every mutation recalculates the escort's aggregate rating via
 * Escort::updateRating() so the denormalized cache stays fresh.
 *
 * All report logic delegates to the Report model — the reports table
 * already links back to reviews via review_id.
 */
class ReviewService
{
    /**
     * Create a review from a member for an escort.
     *
     * The DB unique(user_id, escort_id) constraint prevents duplicate
     * reviews — the caller should catch the IntegrityConstraintViolation
     * and respond with a 409.
     *
     * @param  User  $user  The authenticated member writing the review.
     * @param  array  $data  Validated input: escort_id, rating, comment.
     */
    public function createReview(User $user, array $data): Review
    {
        return DB::transaction(function () use ($user, $data) {
            $review = Review::create([
                'user_id' => $user->id,
                'escort_id' => $data['escort_id'],
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? '',
                'is_verified' => false,
                'is_visible' => true,
            ]);

            $review->escort->updateRating();

            return $review;
        });
    }

    /**
     * Update an existing review (rating and/or comment only).
     */
    public function updateReview(Review $review, array $data): Review
    {
        return DB::transaction(function () use ($review, $data) {
            $review->update([
                'rating' => $data['rating'] ?? $review->rating,
                'comment' => $data['comment'] ?? $review->comment,
            ]);

            $review->escort->updateRating();

            return $review->fresh();
        });
    }

    /**
     * Soft-delete a review and recalculate the escort's rating.
     */
    public function deleteReview(Review $review): void
    {
        DB::transaction(function () use ($review) {
            $escort = $review->escort;

            $review->delete();

            $escort->updateRating();
        });
    }

    /**
     * Submit a report against a review.
     */
    public function reportReview(Review $review, User $reporter, string $reason, ?string $description = null): Report
    {
        return Report::create([
            'review_id' => $review->id,
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'description' => $description,
            'status' => 'pending',
        ]);
    }

    /**
     * Get visible, verified reviews for an escort.
     */
    public function visibleReviewsQuery(Escort $escort): Builder
    {
        return $escort->reviews()
            ->visible()
            ->verified()
            ->with('user')
            ->latest();
    }

    /**
     * Get all reviews (including invisible/unverified) for admin use.
     */
    public function allReviewsForEscort(Escort $escort): Collection
    {
        return $escort->reviews()->with('user')->latest()->get();
    }
}

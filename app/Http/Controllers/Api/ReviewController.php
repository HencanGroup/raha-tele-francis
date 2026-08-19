<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReportReviewRequest;
use App\Http\Requests\Api\StoreReviewRequest;
use App\Http\Requests\Api\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Escort;
use App\Models\Review;
use App\Services\Review\ReviewService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API review controller consumed by the Inertia frontend.
 *
 * index   — public list of visible+verified reviews for an escort
 * store   — member creates a review for an escort
 * show    — view a single review (visibility-gated)
 * update  — review author edits their review
 * destroy — review author deletes their review
 * report  — any authenticated user reports a review
 */
class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    /**
     * List visible, verified reviews for an escort.
     *
     * GET /api/escorts/{escort}/reviews
     */
    public function index(Request $request, Escort $escort): JsonResponse
    {
        $perPage = min($request->integer('per_page', 10), 50);

        $reviews = $this->reviewService
            ->visibleReviewsQuery($escort)
            ->paginate($perPage);

        // The route is public but xios sends the Sanctum Bearer token, so we
        // can still resolve the current member and tell the frontend whether
        // they have already reviewed this escort (to hide the write button).
        $currentUser = auth('sanctum')->user();
        $hasReviewed = $currentUser
            ? Review::where('user_id', $currentUser->id)
                ->where('escort_id', $escort->id)
                ->exists()
            : false;

        return response()->json([
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'has_reviewed' => $hasReviewed,
            ],
        ]);
    }

    /**
     * Create a review for an escort.
     *
     * POST /api/reviews
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isMember()) {
            return response()->json(['message' => 'Only members can write reviews.'], 403);
        }

        try {
            $review = $this->reviewService->createReview($user, $request->validated());
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Integrity constraint violation')) {
                return response()->json(['message' => 'You have already reviewed this escort.'], 409);
            }

            throw $e;
        }

        $review->load('user', 'escort');

        return response()->json([
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Show a single review.
     *
     * GET /api/reviews/{review}
     *
     * The review author, the escort being reviewed, and admins can
     * always view it. Other users only see visible+verified reviews.
     */
    public function show(Request $request, Review $review): JsonResponse
    {
        $user = Auth::user();

        $canView = $review->is_visible && $review->is_verified;

        if (! $canView) {
            $isOwner = $user && $review->user_id === $user->id;
            $isEscort = $user && $review->escort->user_id === $user->id;
            $isAdmin = $user && $user->isSystemUser();

            if (! ($isOwner || $isEscort || $isAdmin)) {
                return response()->json(['message' => 'Review not found.'], 404);
            }
        }

        $review->load('user', 'escort');

        return response()->json([
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Update a review (rating and/or comment).
     *
     * PUT /api/reviews/{review}
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $user = Auth::user();

        if ($review->user_id !== $user->id) {
            return response()->json(['message' => 'You can only edit your own review.'], 403);
        }

        $review = $this->reviewService->updateReview($review, $request->validated());

        $review->load('user', 'escort');

        return response()->json([
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Delete a review.
     *
     * DELETE /api/reviews/{review}
     */
    public function destroy(Request $request, Review $review): JsonResponse
    {
        $user = Auth::user();

        if ($review->user_id !== $user->id) {
            return response()->json(['message' => 'You can only delete your own review.'], 403);
        }

        $this->reviewService->deleteReview($review);

        return response()->json(['message' => 'Review deleted successfully.']);
    }

    /**
     * Report a review for inappropriate content.
     *
     * POST /api/reviews/{review}/report
     *
     * Creates a pending Report row linked to the review. Admin
     * moderators handle it in the Filament ReportResource.
     */
    public function report(ReportReviewRequest $request, Review $review): JsonResponse
    {
        $user = Auth::user();

        $this->reviewService->reportReview(
            $review,
            $user,
            $request->input('reason'),
            $request->input('description')
        );

        return response()->json(['message' => 'Review reported successfully.'], 201);
    }
}

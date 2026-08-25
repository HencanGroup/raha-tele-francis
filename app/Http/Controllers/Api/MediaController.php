<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMediaRequest;
use App\Http\Resources\EscortMediaResource;
use App\Models\EscortResource;
use App\Services\Escort\EscortMediaService;
use App\Services\Escort\MediaUnlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for escort media (photos/videos) management.
 *
 * Provides CRUD endpoints consumed by the Inertia frontend's media
 * management page. The unlock endpoint allows members to pay credits
 * to view private escort media.
 */
class MediaController extends Controller
{
    public function __construct(
        private readonly EscortMediaService $mediaService,
        private readonly MediaUnlockService $mediaUnlockService,
    ) {}

    /**
     * List the authenticated escort's media items.
     */
    public function index(Request $request): JsonResponse
    {
        $media = $this->mediaService->list($request->user());

        return response()->json([
            'data' => EscortMediaResource::collection($media),
        ]);
    }

    /**
     * Upload a new photo or video.
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $media = $this->mediaService->upload(
            $request->user(),
            $request->file('file'),
            $request->input('caption'),
            $request->boolean('is_public', true),
        );

        return response()->json([
            'data' => new EscortMediaResource($media),
        ], 201);
    }

    /**
     * Delete a media item and its stored file.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->mediaService->delete($request->user(), $id);

        return response()->json([
            'message' => 'Media deleted successfully.',
        ]);
    }

    /**
     * Set a photo as the primary profile photo.
     */
    public function setPrimary(Request $request, int $id): JsonResponse
    {
        $this->mediaService->setPrimary($request->user(), $id);

        return response()->json([
            'message' => 'Primary photo updated.',
        ]);
    }

    /**
     * Toggle the public/private visibility of a media item.
     */
    public function togglePublic(Request $request, int $id): JsonResponse
    {
        $media = $this->mediaService->togglePublic($request->user(), $id);

        return response()->json([
            'data' => new EscortMediaResource($media),
        ]);
    }

    /**
     * Unlock a private media item by spending credits.
     *
     * Members pay a fixed credit cost (config: media_unlock_cost) to view
     * private escort photos/videos. The cost is split between the escort
     * and the platform via CommissionService.
     */
    public function unlock(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->isMember()) {
            return response()->json(['message' => 'Only members can unlock media.'], 403);
        }

        $resource = EscortResource::findOrFail($id);

        // Idempotent — already unlocked is a free no-op.
        $this->mediaUnlockService->unlock($user, $resource);

        return response()->json([
            'message' => 'Media unlocked successfully.',
            'data' => new EscortMediaResource($resource),
        ]);
    }
}

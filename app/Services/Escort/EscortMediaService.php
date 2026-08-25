<?php

namespace App\Services\Escort;

use App\Models\Escort;
use App\Models\EscortResource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Business logic for escort media (photos/videos) management.
 *
 * Handles file storage, metadata creation, primary-photo enforcement,
 * and deletion with storage cleanup. All write operations run inside a
 * DB transaction for consistency.
 */
class EscortMediaService
{
    /**
     * List all media for the authenticated escort, ordered by sort_order.
     */
    public function list(User $user)
    {
        $escort = $user->escortProfile;

        if (! $escort) {
            abort(404, 'Escort profile not found.');
        }

        return EscortResource::where('escort_id', $escort->id)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Upload a new media file and create the associated record.
     *
     * If this is the escort's first photo, it is automatically set as primary.
     */
    public function upload(User $user, UploadedFile $file, ?string $caption = null, bool $isPublic = true): EscortResource
    {
        $escort = $user->escortProfile;

        if (! $escort) {
            abort(404, 'Escort profile not found.');
        }

        // Detect type from MIME.
        $mime = $file->getMimeType();
        $type = str_starts_with($mime, 'video/') ? 'video' : 'photo';

        // Store the file.
        $path = $file->store('escorts/'.$escort->id, uploads_disk());

        // Auto-set primary if this is the first photo.
        $hasExisting = EscortResource::where('escort_id', $escort->id)->exists();
        $isPrimary = ! $hasExisting && $type === 'photo';

        // Compute sort_order — place at the end.
        $maxOrder = EscortResource::where('escort_id', $escort->id)->max('sort_order') ?? 0;

        return DB::transaction(function () use ($escort, $type, $path, $caption, $isPublic, $isPrimary, $maxOrder) {
            return EscortResource::create([
                'escort_id' => $escort->id,
                'type' => $type,
                'path' => $path,
                'caption' => $caption,
                'is_primary' => $isPrimary,
                'is_verified' => false,
                'is_public' => $isPublic,
                'sort_order' => $maxOrder + 1,
            ]);
        });
    }

    /**
     * Delete a media record and its stored file.
     */
    public function delete(User $user, int $mediaId): void
    {
        $escort = $user->escortProfile;

        if (! $escort) {
            abort(404, 'Escort profile not found.');
        }

        $resource = EscortResource::where('escort_id', $escort->id)
            ->findOrFail($mediaId);

        DB::transaction(function () use ($resource) {
            // Remove the file from storage.
            if ($resource->path) {
                Storage::disk(uploads_disk())->delete($resource->path);
            }
            if ($resource->thumbnail_path) {
                Storage::disk(uploads_disk())->delete($resource->thumbnail_path);
            }

            $wasPrimary = $resource->is_primary;
            $resource->delete();

            // If the deleted resource was primary, promote the next photo.
            if ($wasPrimary) {
                $next = EscortResource::where('escort_id', $resource->escort_id)
                    ->photos()
                    ->orderBy('sort_order')
                    ->first();

                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }
        });
    }

    /**
     * Set a media item as the primary photo (unsets all others).
     */
    public function setPrimary(User $user, int $mediaId): void
    {
        $escort = $user->escortProfile;

        if (! $escort) {
            abort(404, 'Escort profile not found.');
        }

        $resource = EscortResource::where('escort_id', $escort->id)
            ->photos()
            ->findOrFail($mediaId);

        DB::transaction(function () use ($resource) {
            // Unset all existing primary photos for this escort.
            EscortResource::where('escort_id', $resource->escort_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $resource->update(['is_primary' => true]);
        });
    }

    /**
     * Toggle the public visibility of a media item.
     */
    public function togglePublic(User $user, int $mediaId): EscortResource
    {
        $escort = $user->escortProfile;

        if (! $escort) {
            abort(404, 'Escort profile not found.');
        }

        $resource = EscortResource::where('escort_id', $escort->id)
            ->findOrFail($mediaId);

        $resource->update(['is_public' => ! $resource->is_public]);

        return $resource;
    }
}

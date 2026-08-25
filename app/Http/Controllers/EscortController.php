<?php

namespace App\Http\Controllers;

use App\Models\Escort;
use App\Models\Favorite;
use App\Services\Escort\MediaUnlockService;
use App\Services\Escort\PhoneUnlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EscortController extends Controller
{
    public function __construct(
        private readonly PhoneUnlockService $phoneUnlockService,
        private readonly MediaUnlockService $mediaUnlockService,
    ) {}

    /* -----------------------------------------------------------------
     | Escort Listing
     |-----------------------------------------------------------------*/

    public function index(Request $request)
    {
        return Inertia::render('Frontend/Escort/Index');
    }

    /* -----------------------------------------------------------------
     | Escort Profile View
     |-----------------------------------------------------------------*/

    public function show(Escort $escort)
    {
        // 🔐 Ensure escort belongs to a valid escort-type user (user_type
        // discriminator — escorts have no Spatie roles; they never use Filament).
        abort_unless(
            $escort->user && $escort->user->isEscort(),
            404
        );

        // 👁️ Increment view count safely
        DB::transaction(function () use ($escort) {
            $escort->increment('view_count');
        });

        // 📦 Eager-load required relationships — ALL resources are loaded so
        // the frontend can show private ones with a blur paywall overlay.
        $escort->load([
            'user',
            'user.county',
            'user.town',
            // The appended credits attribute reads user.escortProfile —
            // load it here so serialization doesn't lazy-load it.
            'user.escortProfile',
            'resources',
            'reviews.user',
            'primaryPhoto',
        ]);

        $user = Auth::user();
        $isFavorited = $user ? Favorite::isFavorited($user->id, $escort->id) : false;

        $escortData = $escort->toArray();
        $escortData['is_favorited'] = $isFavorited;

        // Per-user flag — has this member already paid to reveal this escort's
        // phone number? Drives the direct-dial vs paywall-modal button state.
        // Members only; escorts/system users never unlock, so skip the query.
        $escortData['phone_unlocked'] = $user && $user->isMember()
            ? $this->phoneUnlockService->hasUnlockedPhone($user, $escort)
            : false;

        // Which private media items has this member already paid for?
        // Drives the blur vs clear rendering on the frontend.
        $escortData['unlocked_media'] = $user && $user->isMember()
            ? $this->mediaUnlockService->getUnlockedIds($user, $escort->id)
            : [];

        // Media unlock cost from config — so the frontend knows the price.
        $escortData['media_unlock_cost'] = (int) config('system_settings.media_unlock_cost', 5);

        return Inertia::render('Frontend/Escort/Show', [
            'escort' => $escortData,
        ]);
    }
}

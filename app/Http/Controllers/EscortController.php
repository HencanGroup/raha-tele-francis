<?php

namespace App\Http\Controllers;

use App\Models\Escort;
use App\Models\Favorite;
use App\Services\Escort\PhoneUnlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EscortController extends Controller
{
    public function __construct(
        private readonly PhoneUnlockService $phoneUnlockService,
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

        // 📦 Eager-load required relationships
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

        return Inertia::render('Frontend/Escort/Show', [
            'escort' => $escortData,
        ]);
    }
}

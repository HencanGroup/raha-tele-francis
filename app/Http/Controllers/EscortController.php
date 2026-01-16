<?php

namespace App\Http\Controllers;

use App\Models\Escort;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EscortController extends Controller
{
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
        // 🔐 Ensure escort belongs to a valid user with escort role
        abort_unless(
            $escort->user && $escort->user->hasRole('escort'),
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
            'resources',
            'reviews.user',
            'primaryPhoto',
        ]);

        return Inertia::render('Frontend/Escort/Show', [
            'escort' => $escort
        ]);
    }
}

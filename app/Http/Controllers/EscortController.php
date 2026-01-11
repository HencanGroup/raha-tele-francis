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
            'user',              // includes unified profile accessors
            'county',
            'town',
            'resources',
            'reviews.user',      // reviewer profile
            'primaryPhoto',
        ]);

        return Inertia::render('Frontend/Escort/Show', [
            'escort' => $escort,
            'user' => $escort->user, // explicit for frontend clarity
        ]);
    }

    /* -----------------------------------------------------------------
     | Resource Stubs (Future Use)
     |-----------------------------------------------------------------*/

    public function create()
    {
        // 
    }

    public function store(Request $request)
    {
        // 
    }

    public function edit(Escort $escort)
    {
        // 
    }

    public function update(Request $request, Escort $escort)
    {
        // 
    }

    public function destroy(Escort $escort)
    {
        // 
    }
}

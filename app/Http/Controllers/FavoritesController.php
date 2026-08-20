<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Favorites listing for the dashboard — a member sees the escorts they saved;
 * an escort sees the members who favorited them.
 */
class FavoritesController extends Controller
{
    /**
     * Render the favorites page for the authenticated member or escort.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->isMember()) {
            // Members favorite escorts — eager-load the escort + its user + location.
            $favorites = Favorite::where('user_id', $user->id)
                ->with([
                    'escort.user.county',
                    'escort.user.town',
                    'escort.primaryPhoto',
                ])
                ->latest()
                ->get()
                ->map(fn ($favorite) => $this->formatEscort($favorite->escort));
        } elseif ($user->isEscort()) {
            // Escorts are favorited by members — list those members.
            $favorites = Favorite::where('escort_id', $user->escortProfile?->id ?? 0)
                ->with('user')
                ->latest()
                ->get()
                ->map(fn ($favorite) => $this->formatMember($favorite->user));
        } else {
            abort(403);
        }

        return Inertia::render('Backend/Dashboard/Favorites', [
            'favorites' => $favorites,
            'isMember' => $user->isMember(),
        ]);
    }

    /**
     * Shape an escort for the favorites list (member's saved escorts).
     *
     * @param  \App\Models\Escort|null  $escort
     */
    private function formatEscort($escort): ?array
    {
        if (! $escort) {
            return null;
        }

        return [
            'id' => $escort->id,
            'name' => $escort->user?->name ?? 'Escort',
            'display_name' => $escort->user?->display_name,
            'profile_photo_url' => $escort->user?->profile_photo_url,
            'location' => collect([
                $escort->user?->town?->name,
                $escort->user?->county?->name,
            ])->filter()->join(', '),
        ];
    }

    /**
     * Shape a member for the favorites list (escort's admirers).
     *
     * @param  \App\Models\User|null  $user
     */
    private function formatMember($user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            // member.show binds by Member profile id, not user id.
            'id' => $user->memberProfile?->id,
            'name' => $user->name,
            'display_name' => $user->display_name,
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }
}

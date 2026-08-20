<?php

namespace App\Http\Controllers;

use App\Models\County;
use App\Models\Escort;
use App\Models\Favorite;
use App\Models\Town;
use App\Models\User;
use App\Services\Escort\PhoneUnlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    public function __construct(
        private readonly PhoneUnlockService $phoneUnlockService,
    ) {}

    /* -----------------------------------------------------------------
     | Location Data
     |-----------------------------------------------------------------*/

    public function counties()
    {
        return response()->json(
            County::select('id', 'name')->get()
        );
    }

    public function towns(Request $request)
    {
        $query = Town::select('id', 'name', 'county_id');

        if ($request->filled('county_id')) {
            $query->where('county_id', $request->county_id);
        }

        return response()->json($query->get());
    }

    /* -----------------------------------------------------------------
     | Escorts Listing (role-aware: escorts browse members)
     |-----------------------------------------------------------------*/
    public function escorts(Request $request)
    {
        try {
            $currentUser = Auth::user();

            // An escort account sees members (their chat partners), everyone
            // else (members, guests) sees the public escort feed.
            $isEscortViewingMembers = $currentUser && $currentUser->isEscort();

            $query = User::query();

            if ($isEscortViewingMembers) {
                $query->with(['memberProfile', 'county', 'town'])
                    ->where('user_type', 'member');
            } else {
                $query->with('escortProfile')
                    ->where('user_type', 'escort');
            }

            // County filter
            if ($request->filled('county')) {
                $query->where('county_id', $request->county);
            }

            // Town filter
            if ($request->filled('town')) {
                $query->where('town_id', $request->town);
            }

            // Age range filter (e.g. 26-30)
            if ($request->filled('age_range')) {
                [$minAge, $maxAge] = explode('-', $request->age_range);

                $query->whereBetween('age', [(int) $minAge, (int) $maxAge]);
            }

            if (! $isEscortViewingMembers) {
                // Verified filter (only if checked)
                if ($request->boolean('verified')) {
                    $query->whereHas('escortProfile', function ($q) {
                        $q->where('is_verified', true);
                    });
                }

                // Online filter (only if checked)
                if ($request->boolean('online')) {
                    $query->where('last_seen', '>=', now()->subMinutes(1));
                }

                // Services filter (comma separated)
                if ($request->filled('services')) {
                    // Split the comma-separated string into an array
                    $services = array_map('trim', explode(',', $request->services));

                    $query->whereHas('escortProfile', function ($q) use ($services) {
                        foreach ($services as $service) {
                            $q->where('services', 'like', '%'.$service.'%');
                        }
                    });
                }

                // Sorting
                if ($request->filled('sort')) {
                    match ($request->sort) {
                        // featured
                        'featured' => $query->whereHas('escortProfile', function ($q) {
                            $q->where('featured', true);
                        }),
                        // rating
                        'rating' => $query->whereHas('escortProfile', function ($q) {
                            $q->orderByDesc('rating');
                        }),
                        // newest
                        'newest' => $query->whereHas('escortProfile', function ($q) {
                            $q->latest();
                        }),

                        default => null,
                    };
                }
            }

            $items = $query->latest()->paginate($request->integer('per_page', 12));

            if ($isEscortViewingMembers) {
                // Members carry no escort profile — shape a minimal profile so
                // the escort-side card (MemberCard) can render them.
                $items->getCollection()->transform(function ($memberUser) {
                    return [
                        'id' => $memberUser->id,
                        'name' => $memberUser->name,
                        'display_name' => $memberUser->display_name,
                        'gender' => $memberUser->gender,
                        'age' => $memberUser->age,
                        'profile_photo_url' => $memberUser->profile_photo_url,
                        'location' => collect([
                            $memberUser->town?->name,
                            $memberUser->county?->name,
                        ])->filter()->join(', '),
                        // member.show binds by Member profile id, not user id.
                        'member_id' => $memberUser->memberProfile?->id,
                        'user_type' => 'member',
                    ];
                });
            } else {
                $items->getCollection()->transform(function ($userItem) use ($currentUser) {
                    $escort = $userItem->escortProfile;
                    $isFav = $currentUser ? Favorite::isFavorited($currentUser->id, $escort->id) : false;
                    $escortData = $userItem->toArray();
                    $escortData['escort_profile'] = $escort;
                    $escortData['is_favorited'] = $isFav;

                    return $escortData;
                });
            }

            return response()->json($items);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /* -----------------------------------------------------------------
     | Phone Unlock (Credits)
     |-----------------------------------------------------------------*/

    public function unlockPhone(Request $request)
    {
        try {
            $request->validate([
                'escort_id' => 'required|integer|exists:escorts,id',
            ]);

            $user = Auth::user();
            $escort = Escort::findOrFail($request->escort_id);
            $cost = (int) config('services.system_variables.phone_unlock_cost', 10);

            if (! $user->hasSufficientCredits($cost)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient credits',
                ], 422);
            }

            $this->phoneUnlockService->unlock($user, $escort);

            return response()->json([
                'success' => true,
                'message' => 'Phone number unlocked successfully',
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /* -----------------------------------------------------------------
     | Favorites
     |-----------------------------------------------------------------*/

    public function toggleFavorite(Request $request)
    {
        try {
            $request->validate([
                'escort_id' => 'required|exists:escorts,id',
            ]);

            $user = Auth::user();
            $escort = Escort::findOrFail($request->escort_id);

            $isFavorited = Favorite::toggle($user->id, $escort->id);

            return response()->json([
                'success' => true,
                'is_favorited' => $isFavorited,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}

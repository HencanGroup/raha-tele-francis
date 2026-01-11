<?php

namespace App\Http\Controllers;

use App\Models\County;
use App\Models\Town;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    /* -----------------------------------------------------------------
     | Location Data
     |-----------------------------------------------------------------*/

    public function counties()
    {
        return response()->json(
            County::select('id', 'name')->get()
        );
    }

    public function towns()
    {
        return response()->json(
            Town::select('id', 'name', 'county_id')->get()
        );
    }

    /* -----------------------------------------------------------------
     | Escorts Listing
     |-----------------------------------------------------------------*/

    public function escorts(Request $request)
    {
        try {
            $perPage = $request->integer('per_page', 12);

            $query = User::query()
                ->with('escortProfile')
                ->whereHas('roles', fn($q) => $q->where('name', 'escort'));

            if ($request->filled('county')) {
                $query->whereHas(
                    'escortProfile',
                    fn($q) => $q->where('county_id', $request->county)
                );
            }

            if ($request->filled('town')) {
                $query->whereHas(
                    'escortProfile',
                    fn($q) => $q->where('town_id', $request->town)
                );
            }

            return response()->json(
                $query->paginate($perPage)
            );

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
            $user = Auth::user();
            $cost = config('services.system_variables.phone_unlock_cost');

            if ($user->credits < $cost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient credits',
                ], 422);
            }

            DB::transaction(function () use ($user, $cost) {
                $user->decrement('credits', $cost);
                $user->increment('total_credits_spent', $cost);
            });

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
}

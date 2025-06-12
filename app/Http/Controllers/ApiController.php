<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    public function plans(Request $request)
    {
        $plans = Plan::all();
        if ($plans->isNotEmpty()) {
            return response()->json($plans);
        }
        return response()->json([]);
    }

    public function newEscorts(Request $request)
    {
        try {
            $newEscorts = User::where('status', 'active')
                ->where('is_escort', true)
                ->where('is_verified', true)
                ->orderBy('created_at', 'desc')
                ->take(4)
                ->get();

            return response()->json($newEscorts);
        } catch (\Throwable $th) {
            Log::error('Failed to fetch new escorts: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve escorts',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function nearbyEscorts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
                'distance' => 'required|numeric|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parameters',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lat = $request->input('lat');
            $lng = $request->input('lng');
            $distance = $request->input('distance');

            $escorts = User::where('status', 'active')
                ->where('is_escort', true)
                ->where('is_verified', true)
                ->selectRaw("*, 
                    (6371 * acos(cos(radians(?)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians(?)) 
                    + sin(radians(?)) 
                    * sin(radians(latitude)))) AS distance", [$lat, $lng, $lat])
                ->having('distance', '<=', $distance)
                ->orderBy('distance')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $escorts,
                'count' => $escorts->count()
            ]);
        } catch (\Throwable $th) {
            Log::error('Nearby escorts search failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve nearby escorts',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}

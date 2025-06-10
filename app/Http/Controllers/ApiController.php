<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function plans(Request $request)
    {
        // This method is intended to return a list of plans.
        // Currently, it returns an empty JSON response.
        $plans = Plan::all();
        if ($plans->isNotEmpty()) {
            return response()->json($plans);
        }
        // If no plans are found, return an empty JSON response. 
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve escorts',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}

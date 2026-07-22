<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditTransactionResource;
use App\Http\Resources\EarningsResource;
use App\Models\CreditTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for the escort earnings dashboard (Phase 3 — Monetization).
 *
 * Exposes two endpoints consumed by the Next.js frontend:
 *   GET /api/earnings          — current earnings/balance summary
 *   GET /api/earnings/transactions — paginated transaction history
 *
 * Both endpoints require the authenticated user to be an escort, enforced via
 * the isEscort() helper on the User model.
 */
class EarningsController extends Controller
{
    /**
     * Return the authenticated escort's earnings summary.
     *
     * Reads directly from the Escort profile (earnings, balance fields).
     * Commission/wallet logic is not yet wired to write CreditTransactions for
     * escort earnings — this endpoint will reflect the correct values once the
     * commission service is implemented (Phase 3).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isEscort()) {
            return response()->json(['message' => 'Only escorts can access earnings.'], 403);
        }

        $escort = $user->escortProfile;

        if (! $escort) {
            return response()->json(['message' => 'Escort profile not found.'], 404);
        }

        return response()->json([
            'data' => new EarningsResource($escort),
        ]);
    }

    /**
     * Return the authenticated escort's paginated credit transaction history.
     *
     * Queries CreditTransaction rows linked to the user. Supports optional
     * type filtering via the `type` query parameter.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isEscort()) {
            return response()->json(['message' => 'Only escorts can access earnings.'], 403);
        }

        $query = CreditTransaction::where('user_id', $user->id)
            ->latest();

        // Optional type filter (purchase, usage, bonus, withdrawal, commission).
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $perPage = min((int) $request->integer('per_page', 20), 100);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'data' => CreditTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}

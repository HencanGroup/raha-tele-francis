<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWithdrawalRequest;
use App\Http\Resources\WithdrawalResource;
use App\Models\Withdrawal;
use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for escort credit withdrawals (M-Pesa B2C payouts).
 *
 * Exposes two endpoints consumed by the Inertia frontend:
 *   POST /api/withdrawals  — request a new withdrawal
 *   GET  /api/withdrawals  — list the caller's withdrawals
 *
 * All business logic delegates to WithdrawalService; the controller stays thin.
 */
class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService,
    ) {}

    /**
     * Create a pending withdrawal request for the authenticated escort.
     */
    public function store(StoreWithdrawalRequest $request): JsonResponse
    {
        $withdrawal = $this->withdrawalService->request(
            $request->user(),
            (float) $request->float('amount'),
            $request->input('phone_number'),
        );

        return response()->json([
            'data' => new WithdrawalResource($withdrawal),
        ], 201);
    }

    /**
     * List the authenticated escort's withdrawal requests, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => WithdrawalResource::collection($withdrawals),
            'meta' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ],
        ]);
    }
}

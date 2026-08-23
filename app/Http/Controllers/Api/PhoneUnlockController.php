<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UnlockPhoneRequest;
use App\Models\Escort;
use App\Services\Escort\PhoneUnlockService;

/**
 * Controller for phone number unlock credit flow.
 *
 * Delegates all business logic to PhoneUnlockService — the controller
 * only validates input, calls the service, and returns a response.
 */
class PhoneUnlockController extends Controller
{
    public function __construct(
        private readonly PhoneUnlockService $phoneUnlockService,
    ) {}

    /**
     * Unlock an escort's phone number by spending credits.
     *
     * Idempotent: a member who already paid for this escort skips the wallet
     * check entirely and receives success without being charged again.
     * Responds with the fresh wallet balance so the UI can update instantly.
     */
    public function unlock(UnlockPhoneRequest $request)
    {
        $user = $request->user();
        $escort = Escort::findOrFail($request->escort_id);

        // Already-unlocked members pass straight through — no charge, no
        // insufficient-credits rejection on repeat requests.
        if (! $this->phoneUnlockService->hasUnlockedPhone($user, $escort)) {
            $cost = (int) config('services.system_variables.phone_unlock_cost', 10);

            if (! $user->hasSufficientCredits($cost)) {
                return response()->json([
                    'message' => 'Insufficient credits.',
                ], 409);
            }

            $this->phoneUnlockService->unlock($user, $escort);
        }

        return response()->json([
            'data' => [
                'success' => true,
                'message' => 'Phone number unlocked successfully.',
                // Fresh balance so the frontend coin display updates without a reload.
                'credits' => (float) $user->fresh()->credits,
            ],
        ]);
    }
}

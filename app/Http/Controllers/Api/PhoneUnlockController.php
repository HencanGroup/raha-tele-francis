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
     * Validates the request, checks sufficient credits, delegates to
     * the service (commission split + ledger + escort crediting).
     */
    public function unlock(UnlockPhoneRequest $request)
    {
        $user = $request->user();
        $escort = Escort::findOrFail($request->escort_id);

        $cost = (int) config('services.system_variables.phone_unlock_cost', 10);

        if (! $user->hasSufficientCredits($cost)) {
            return response()->json([
                'message' => 'Insufficient credits.',
            ], 409);
        }

        $this->phoneUnlockService->unlock($user, $escort);

        return response()->json([
            'data' => [
                'success' => true,
                'message' => 'Phone number unlocked successfully.',
            ],
        ]);
    }
}

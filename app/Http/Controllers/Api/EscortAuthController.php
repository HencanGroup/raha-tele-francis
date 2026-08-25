<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEscortRegistrationRequest;
use App\Http\Resources\RegisteredEscortResource;
use App\Services\Escort\EscortRegistrationService;
use Illuminate\Http\JsonResponse;

/**
 * API controller for public escort self-registration.
 *
 * Creates a pending escort application and issues a Sanctum token so the
 * frontend can log the escort in immediately. All business logic lives in
 * EscortRegistrationService — the controller stays thin.
 */
class EscortAuthController extends Controller
{
    public function __construct(
        private readonly EscortRegistrationService $registrationService,
    ) {}

    /**
     * Register a new escort application.
     *
     * @return JsonResponse 201 with token + user data wrapped in `data`.
     */
    public function register(StoreEscortRegistrationRequest $request): JsonResponse
    {
        $user = $this->registrationService->register($request->validated());

        // Send the registration-confirmation email (queued, non-blocking).
        $this->registrationService->sendConfirmationEmail($user);

        $token = $user->createToken('escort-registration')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => new RegisteredEscortResource($user),
            ],
        ], 201);
    }
}

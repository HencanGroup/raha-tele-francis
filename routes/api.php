<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\EarningsController;
use App\Http\Controllers\Api\EscortAuthController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PhoneUnlockController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TwoFactorAuthController;
use App\Http\Controllers\Api\WithdrawalController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\MpesaController;
use Illuminate\Support\Facades\Route;

/**
 * ---------------------------------------------------
 * Public API Endpoints for Fetching Site Data
 * ---------------------------------------------------
 */
Route::prefix('/data')->group(function () {
    Route::get('/counties', [ApiController::class, 'counties'])->name('api.counties');
    Route::get('/towns', [ApiController::class, 'towns'])->name('api.towns');
});

/* -----------------------------------------------------------------
 | Auth (Login + 2FA)
 |-----------------------------------------------------------------*/

// Login — email/password authentication (no auth)
Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');

// Public escort self-registration — creates a pending application (no auth).
Route::post('/escort/register', [EscortAuthController::class, 'register'])->name('api.escort.register');

// 2FA challenge — verify with TOTP code or recovery code (no auth, requires two_factor_token)
Route::post('/auth/2fa/verify', [TwoFactorAuthController::class, 'verify'])->name('api.auth.2fa.verify');
Route::post('/auth/2fa/recovery', [TwoFactorAuthController::class, 'recovery'])->name('api.auth.2fa.recovery');

// Logout + 2FA management (auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    Route::prefix('/auth/2fa')->group(function () {
        Route::get('/status', [TwoFactorAuthController::class, 'status'])->name('api.auth.2fa.status');
        Route::post('/enable', [TwoFactorAuthController::class, 'enable'])->name('api.auth.2fa.enable');
        Route::post('/confirm', [TwoFactorAuthController::class, 'confirm'])->name('api.auth.2fa.confirm');
        Route::post('/disable', [TwoFactorAuthController::class, 'disable'])->name('api.auth.2fa.disable');
    });
});

/* -----------------------------------------------------------------
 | Social Auth (OAuth)
 |-----------------------------------------------------------------*/
Route::prefix('/auth')->group(function () {
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback']);
});

Route::prefix('/payments')->group(function () {
    Route::post('/callback', [MpesaController::class, 'callback'])->name('payments.callback');
    Route::post('/confirmation', [MpesaController::class, 'confirmation'])->name('payments.confirmation');
    Route::post('/validation', [MpesaController::class, 'validation'])->name('payments.validation');
    Route::post('/timeout', [MpesaController::class, 'timeout'])->name('payments.timeout');
    Route::post('/result', [MpesaController::class, 'result'])->name('payments.result');
    // B2C payout callbacks for escort withdrawals (public — verified by reference).
    Route::post('/b2c/result', [MpesaController::class, 'b2cResult'])->name('payments.b2c.result');
    Route::post('/b2c/timeout', [MpesaController::class, 'b2cTimeout'])->name('payments.b2c.timeout');
});

/* -----------------------------------------------------------------
 | Chat (paid messages — Phase 3 Monetization)
 |-----------------------------------------------------------------*/
Route::middleware('auth:sanctum')->prefix('/chat')->group(function () {
    Route::post('/messages', [ChatController::class, 'sendMessage'])->name('api.chat.send');
    Route::post('/messages/{message}/unlock', [ChatController::class, 'unlockMessage'])->name('api.chat.unlock');
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('api.chat.messages');
    Route::post('/messages/{message}/reactions', [ChatController::class, 'addReaction'])->name('api.chat.reactions.add');
    Route::delete('/messages/{message}/reactions', [ChatController::class, 'removeReaction'])->name('api.chat.reactions.remove');
});

/* -----------------------------------------------------------------
 | Earnings (escort dashboard — Phase 3 Monetization)
 |-----------------------------------------------------------------*/
Route::middleware('auth:sanctum')->prefix('/earnings')->group(function () {
    Route::get('/', [EarningsController::class, 'index'])->name('api.earnings.index');
    Route::get('/transactions', [EarningsController::class, 'history'])->name('api.earnings.history');
});

/* -----------------------------------------------------------------
 | Reviews (Phase 4 — UI & Polish)
 |-----------------------------------------------------------------*/

// Public — list visible, verified reviews for an escort
Route::get('/escorts/{escort}/reviews', [ReviewController::class, 'index'])->name('api.reviews.index');

// Authenticated — create, view, update, delete, and report reviews
Route::middleware('auth:sanctum')->prefix('/reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'store'])->name('api.reviews.store');
    Route::get('/{review}', [ReviewController::class, 'show'])->name('api.reviews.show');
    Route::put('/{review}', [ReviewController::class, 'update'])->name('api.reviews.update');
    Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('api.reviews.destroy');
    Route::post('/{review}/report', [ReviewController::class, 'report'])->name('api.reviews.report');
});

/* -----------------------------------------------------------------
 | Escort Phone Unlock (Phase 2 — Core Systems)
 |-----------------------------------------------------------------*/
Route::middleware('auth:sanctum')->post('/escorts/{escort}/unlock-phone', [PhoneUnlockController::class, 'unlock'])->name('api.escorts.unlock-phone');

/* -----------------------------------------------------------------
 | Escort Withdrawals (Phase 2 — Core Systems, M-Pesa B2C)
 |-----------------------------------------------------------------*/
Route::middleware('auth:sanctum')->prefix('/withdrawals')->group(function () {
    Route::get('/', [WithdrawalController::class, 'index'])->name('api.withdrawals.index');
    Route::post('/', [WithdrawalController::class, 'store'])->name('api.withdrawals.store');
});

/* -----------------------------------------------------------------
 | Escort Media (photos/videos — Phase 5 Frontend UI)
 |-----------------------------------------------------------------*/
Route::middleware('auth:sanctum')->prefix('/media')->group(function () {
    Route::get('/', [MediaController::class, 'index'])->name('api.media.index');
    Route::post('/', [MediaController::class, 'store'])->name('api.media.store');
    Route::delete('/{id}', [MediaController::class, 'destroy'])->name('api.media.destroy');
    Route::post('/{id}/primary', [MediaController::class, 'setPrimary'])->name('api.media.set-primary');
    Route::post('/{id}/toggle-public', [MediaController::class, 'togglePublic'])->name('api.media.toggle-public');
    Route::post('/{id}/unlock', [MediaController::class, 'unlock'])->name('api.media.unlock');
});

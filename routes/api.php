<?php

use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\ApiController;
// Controllers
use App\Http\Controllers\MpesaController;
use Illuminate\Support\Facades\Route;

/**
 * ---------------------------------------------------
 * Public API Endpoints for Fetching Site Data
 * ---------------------------------------------------
 * These routes provide data for frontend components.
 * Auth is optional - we check if user is authenticated in the controller.
 */
Route::prefix('/data')->group(function () {
    Route::get('/counties', [ApiController::class, 'counties'])->name('api.counties');
    Route::get('/towns', [ApiController::class, 'towns'])->name('api.towns');
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
});

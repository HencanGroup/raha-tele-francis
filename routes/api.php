<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Transaction\MpesaController;

/**
 * ---------------------------------------------------
 * Public API Endpoints for Fetching Site Data
 * ---------------------------------------------------
 * These routes provide data for frontend components.
 */
Route::prefix('/data')->group(function () {
    Route::get('/plans', [ApiController::class, 'plans'])->name('api.plans');
    Route::get('/new-escorts', [ApiController::class, 'newEscorts'])->name('api.new-escorts');
    Route::get('/nearby-escorts', [ApiController::class, 'nearbyEscorts'])->name('api.nearby.escorts');
});

Route::prefix('/payments')->group(function () {
    Route::post('/callback', [MpesaController::class, 'callback'])->name('payments.callback');
    Route::post('/confirmation', [MpesaController::class, 'confirmation'])->name('payments.confirmation');
    Route::post('/validation', [MpesaController::class, 'validation'])->name('payments.validation');
    Route::post('/timeout', [MpesaController::class, 'timeout'])->name('payments.timeout');
    Route::post('/result', [MpesaController::class, 'result'])->name('payments.result');
});

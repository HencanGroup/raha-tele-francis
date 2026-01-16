<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\ApiController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MpesaController;

/**
 * ---------------------------------------------------
 * Public API Endpoints for Fetching Site Data
 * ---------------------------------------------------
 * These routes provide data for frontend components.
 */
Route::prefix('/data')->group(function () {
    Route::get('/counties', [ApiController::class, 'counties'])->name('api.counties');
    Route::get('/towns', [ApiController::class, 'towns'])->name('api.towns');
    Route::get('/escorts', [ApiController::class, 'escorts'])->name('api.escorts');
});

Route::prefix('/payments')->group(function () {
    Route::post('/callback', [MpesaController::class, 'callback'])->name('payments.callback');
    Route::post('/confirmation', [MpesaController::class, 'confirmation'])->name('payments.confirmation');
    Route::post('/validation', [MpesaController::class, 'validation'])->name('payments.validation');
    Route::post('/timeout', [MpesaController::class, 'timeout'])->name('payments.timeout');
    Route::post('/result', [MpesaController::class, 'result'])->name('payments.result');
});

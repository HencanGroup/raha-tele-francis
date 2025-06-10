<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\ApiController;

/**
 * ---------------------------------------------------
 * Public API Endpoints for Fetching Site Data
 * ---------------------------------------------------
 * These routes provide data for frontend components.
 */
Route::prefix('/data')->group(function () {
    Route::get('/plans', [ApiController::class, 'plans'])->name('api.plans');
    Route::get('/new-escorts', [ApiController::class, 'newEscorts'])->name('api.new-escorts');
});

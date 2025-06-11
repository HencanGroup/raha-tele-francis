<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Transaction\MpesaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Frontend/Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register')
    ]);
});

Route::get('/dating-advice', function () {
    return Inertia::render('Frontend/Dating/Index');
});

Route::get('/singles-near-me', function () {
    return Inertia::render('Frontend/Singles/Index');
})->name('singles-near-me');

Route::resources([
    'plan'     => PlanController::class,
]);

Route::get('/checkout', function () {
    return Inertia::render('Frontend/Plan/Checkout');
})->name('checkout.index');

Route::middleware(['auth', 'verified', 'profileStatus', 'subscription'])->group(function () {
    /* -------------------------------------------------
     * DASHBOARD ROUTES
     * ------------------------------------------------- */
    Route::middleware(['adminAccessCheck'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    Route::resources([
        'profile' => ProfileController::class,
    ]);

    /* -------------------------------------------------
     * MPESA TRANSACTION ROUTES
     * ------------------------------------------------- */
    Route::prefix('mpesa')->group(function () {
        Route::post('/pay', [MpesaController::class, 'stk'])->name('mpesa.pay');
    });
});

require __DIR__ . '/auth.php';

<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EscortController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MpesaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Frontend/Home');
});

Route::resources([
    'escort' => EscortController::class,
]);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resources([
        'conversation' => ConversationController::class,
        'message' => MessageController::class,
    ]);

    Route::post('/unlock-phone', [ApiController::class, 'unlockPhone'])->name('phone.unlock');
    Route::post('/mpesa/stk-push', [MpesaController::class, 'stkPush'])->name('mpesa.stk-push');
});

require __DIR__ . '/auth.php';

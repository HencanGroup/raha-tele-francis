<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\SessionBridgeController;
use App\Http\Controllers\Auth\SessionTokenController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EscortController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| Routes that can be accessed without authentication.
| Example: Home page, public escorts listing, etc.
*/

Route::get('/', function () {
    return Inertia::render('Frontend/Home');
});

// Swap a Sanctum API login into the web session (see SessionBridgeController).
Route::post('/auth/bridge', SessionBridgeController::class)->name('auth.bridge');

// Social login callback — captures the Sanctum token from the OAuth redirect.
Route::get('/auth/social/callback', function () {
    return Inertia::render('Auth/SocialCallback');
})->name('auth.social.callback');

// Mint a Sanctum token for the session-authenticated user (API calls).
Route::post('/auth/issue-token', SessionTokenController::class)
    ->middleware('auth')
    ->name('auth.issue-token');

// Public resource routes (accessible without auth)
Route::resources([
    'escort' => EscortController::class,
]);

// Public member profile — viewed by escorts chatting with the member.
Route::get('/member/{member}', [MemberController::class, 'show'])->name('member.show');

/*
|--------------------------------------------------------------------------
| Escorts API (public — home page listing; is_favorited only when authed)
|--------------------------------------------------------------------------
*/
Route::get('/escorts', [ApiController::class, 'escorts'])->name('escorts.index');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
| Routes that require authentication and email verification.
| Example: Dashboard, conversations, messages, API actions.
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard Route
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboard widget targets — favorites and credit-ledger listings.
    Route::get('/favorites', [FavoritesController::class, 'index'])->name('favorites.index');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    /*
    |--------------------------------------------------------------------------
    | Settings Route
    |--------------------------------------------------------------------------
    */
    Route::get('/settings/security', [SettingsController::class, 'security'])->name('security.settings');

    /*
    |--------------------------------------------------------------------------
    | Chat Route
    |--------------------------------------------------------------------------
    */
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/users', [ChatController::class, 'getUsers'])->name('chat.users');
    // Archived list must be registered before /chat/{conversation} so it is
    // not captured by route-model binding.
    Route::get('/chat/archived', [ChatController::class, 'archived'])->name('chat.archived');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');

    Route::post('/chat/start', [ChatController::class, 'startConversation'])->name('chat.start');
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/{conversation}/read', [ChatController::class, 'markAsRead'])->name('chat.read');
    Route::post('/chat/{conversation}/typing', [ChatController::class, 'typing'])->name('chat.typing');
    Route::post('/chat/{conversation}/archive', [ChatController::class, 'toggleArchive'])->name('chat.archive');
    Route::post('/chat/{conversation}/mute', [ChatController::class, 'toggleMute'])->name('chat.mute');
    Route::post('/chat/{conversation}/block', [ChatController::class, 'toggleBlock'])->name('chat.block');

    Route::delete('/chat/{conversation}', [ChatController::class, 'destroy'])->name('chat.destroy');

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('user-management')->group(function () {
        Route::post('/unlock-phone', [ApiController::class, 'unlockPhone'])->name('phone.unlock');
        Route::post('/mpesa/stk-push', [MpesaController::class, 'stkPush'])->name('mpesa.stk-push');
    });

    /*
    |--------------------------------------------------------------------------
    | Favorites
    |--------------------------------------------------------------------------
    */
    Route::post('/favorites/toggle', [ApiController::class, 'toggleFavorite'])->name('favorites.toggle');

    /*
    |--------------------------------------------------------------------------
    | Heartbeat Route
    |--------------------------------------------------------------------------
    */
    Route::get('/heartbeat', function () {
        return response()->json(['status' => 'ok']);
    })->middleware(['throttle:10,1'])->name('heartbeat');
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
| Routes generated by Laravel Breeze / Jetstream / custom auth system.
*/
require __DIR__.'/auth.php';

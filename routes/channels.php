<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    Log::info('🔐 Channel Authentication Check', [
        'authenticated_user_id' => $user ? $user->id : 'NULL',
        'requested_channel_user_id' => $userId,
        'authorized' => $user && (int) $user->id === (int) $userId,
        'user_agent' => request()->header('User-Agent'),
        'ip' => request()->ip(),
        'session_id' => session()->getId()
    ]);

    // Check if user is authenticated
    if (!$user) {
        Log::warning('❌ User not authenticated');
        return false;
    }

    return (int) $user->id === (int) $userId;
});
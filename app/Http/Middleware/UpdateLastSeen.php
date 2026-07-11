<?php

namespace App\Http\Middleware;

use App\Models\Message;
use Closure;
use Illuminate\Http\Request;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        $user->update(['last_seen' => now()]);

        // Mark incoming messages as delivered
        Message::where('receiver_id', $user->id)
            ->where('is_delivered', false)
            ->update([
                'is_delivered' => true,
                'delivered_at' => now(),
            ]);

        return $next($request);
    }
}

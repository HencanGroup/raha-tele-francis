<?php

namespace App\Http\Middleware;

use App\Models\Conversation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckConversationExistence
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Route param: /conversations/{conversation}
        $otherUserId = (int) $request->route('conversation');

        // Prevent self-conversation
        if ($user->id === $otherUserId) {
            abort(403, 'You cannot start a conversation with yourself.');
        }

        // Find existing conversation (bidirectional)
        $conversation = Conversation::between(
            $user->id,
            $otherUserId
        )->first();

        // Create conversation if it doesn't exist
        if (! $conversation) {
            $conversation = Conversation::create([
                'user_one_id' => $user->id,
                'user_two_id' => $otherUserId,
                'status' => 'active',
            ]);
        }

        // Share conversation with controller
        $request->attributes->set('chatConversation', $conversation);

        return $next($request);
    }
}

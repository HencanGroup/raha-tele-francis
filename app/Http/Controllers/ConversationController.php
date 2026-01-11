<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-conversation-existence')->only('show');
    }

    /**
     * Display all conversations (sidebar).
     */
    public function index()
    {
        $user = Auth::user();

        return Inertia::render('Backend/Conversation/Index', [
            'conversations' => $this->getUserConversations($user),
            'authUserId' => $user->id,
        ]);
    }

    /**
     * Display a specific conversation with messages.
     */
    public function show(Request $request)
    {
        $user = Auth::user();

        /** @var ChatConversation $chatConversation */
        $chatConversation = $request->get('chatConversation');

        $messages = $chatConversation->messages()
            ->with(['sender', 'receiver'])
            ->oldest()
            ->get();

        return Inertia::render('Backend/Conversation/Index', [
            'chatConversation' => $chatConversation->load(['userOne', 'userTwo']),
            'messages' => $messages,
            'conversations' => $this->getUserConversations($user),
            'authUserId' => $user->id,
        ]);
    }

    /**
     * Fetch conversations for authenticated user.
     */
    protected function getUserConversations($user)
    {
        return $user->conversations()
            ->with([
                'userOne',
                'userTwo',
                'latestMessage.sender',
            ])
            ->orderByDesc('last_message_at')
            ->get();
    }
}

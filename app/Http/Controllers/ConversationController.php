<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Models\Message;
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

    public function show(Request $request)
    {
        $user = Auth::user();

        $chatConversation = $request->get('chatConversation');

        // 1️⃣ Get unread incoming messages
        $messagesToRead = Message::where('conversation_id', $chatConversation->id)
            ->where('receiver_id', $user->id)
            ->where('is_delivered', true)
            ->where('is_read', false)
            ->get();

        // 2️⃣ Mark them as read in DB
        Message::whereIn('id', $messagesToRead->pluck('id'))
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        // 3️⃣ Broadcast read receipts
        $messagesToRead->each(
            fn($message) => broadcast(new MessageRead($message))
        );

        // 4️⃣ Load messages
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

<?php

namespace App\Http\Controllers;

use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\FacadesLog;

class MessageController extends Controller
{
    // Get conversations for authenticated user
    public function conversations(Request $request)
    {
        try {
            $user = Auth::user();

            // Get the user's conversations with the necessary relationships
            $conversations = $user->conversations()
                ->with([
                    'userOne:id,name,gender,profile_picture,last_seen',
                    'userTwo:id,name,gender,profile_picture,last_seen',
                    'latestMessage.sender'
                ])
                ->orderByDesc('last_message_at')
                ->get();

            // Map conversations into structured array
            $formattedConversations = $conversations->map(function ($conversation) use ($user) {
                $otherUser = $conversation->user_one_id === $user->id ? $conversation->userTwo : $conversation->userOne;
                $unreadCount = $conversation->unreadMessagesForUser($user->id)->count();

                return [
                    'id' => $conversation->id,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'gender' => $otherUser->gender,
                        'profile_picture' => $otherUser->profile_picture,
                        'online' => $otherUser->is_online ?? false,
                        'last_seen' => $otherUser->last_seen,
                    ],
                    'last_message' => $conversation->latestMessage ? [
                        'content' => $conversation->latestMessage->message,
                        'created_at' => $conversation->latestMessage->created_at,
                        'sender_id' => $conversation->latestMessage->sender_id,
                    ] : null,
                    'unread_count' => $unreadCount,
                    'is_muted' => $conversation->isMutedForUser($user->id),
                    'is_archived' => $conversation->isArchivedForUser($user->id),
                    'is_blocked' => $conversation->isBlockedForUser($user->id),
                    'last_message_at' => $conversation->last_message_at,
                ];
            })->sortByDesc('last_message_at')->values();

            return response()->json($formattedConversations);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get messages for a specific conversation
    public function messages(Conversation $conversation, Request $request)
    {
        $user = Auth::user();

        // Verify user is part of conversation
        if (!in_array($user->id, [$conversation->user_one_id, $conversation->user_two_id])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->visibleForUser($user->id)
            ->with(['sender:id,name,profile_picture'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) use ($user) {
                return [
                    'id' => $message->id,
                    'content' => $message->message,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'is_sent' => $message->is_sent,
                    'is_delivered' => $message->is_delivered,
                    'is_read' => $message->is_read,
                    'is_edited' => $message->is_edited,
                    'type' => $message->type,
                    'attachment' => $message->attachment_path ? [
                        'path' => $message->attachment_path,
                        'name' => $message->attachment_name,
                        'size' => $message->attachment_size,
                        'mime' => $message->attachment_mime,
                    ] : null,
                    'created_at' => $message->created_at,
                    'read_at' => $message->read_at,
                    'reactions' => $message->reactions,
                    'metadata' => $message->metadata,
                    'requires_credit' => $message->requires_credit,
                    'is_paid' => $message->is_paid,
                ];
            });

        // Mark messages as read
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json($messages);
    }

    // Send a new message
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
            'type' => 'nullable|in:text,image,video,audio,file',
        ]);

        $sender = Auth::user();
        $receiver = User::findOrFail($request->receiver_id);

        // Find or create conversation
        $conversation = Conversation::between($sender->id, $receiver->id)->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_one_id' => $sender->id,
                'user_two_id' => $receiver->id,
                'status' => 'active',
                'last_message_at' => now(),
            ]);
        } else {
            $conversation->update(['last_message_at' => now()]);
        }

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message' => $request->message,
            'type' => $request->type ?? 'text',
            'is_sent' => true,
            'sent_at' => now(),
        ]);

        // Load relationships
        $message->load(['sender:id,name,profile_picture']);

        // Broadcast via WebSocket
        broadcast(new \App\Events\MessageSent($message, $sender))->toOthers();

        return response()->json([
            'id' => $message->id,
            'content' => $message->message,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'sender' => [
                'id' => $message->sender->id,
                'name' => $message->sender->display_name,
                'profile_picture' => $message->sender->profile_picture,
            ],
            'created_at' => $message->created_at->format('Y-m-d H:i:s'),
            'read_at' => $message->read_at,
            'is_sent' => $message->is_sent,
            'is_delivered' => $message->is_delivered,
            'is_read' => $message->is_read,
        ]);
    }

    // Mark message as read
    public function markAsRead(Message $message)
    {
        $user = Auth::user();

        if ($message->receiver_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        broadcast(new \App\Events\MessageRead($message, $user))->toOthers();

        return response()->json(['success' => true]);
    }

    // Delete message (soft delete for user)
    public function destroy(Message $message, Request $request)
    {
        $user = Auth::user();

        if ($message->sender_id === $user->id) {
            $message->update([
                'user_one_deleted' => $user->id === $message->conversation->user_one_id,
                'user_two_deleted' => $user->id === $message->conversation->user_two_id,
                'user_one_deleted_at' => $user->id === $message->conversation->user_one_id ? now() : null,
                'user_two_deleted_at' => $user->id === $message->conversation->user_two_id ? now() : null,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function typing(Request $request)
    {
        // TEST LOGGING - Add this first
        Log::info('Typing endpoint called', [
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);

        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'conversation_id' => 'required|exists:conversations,id',
            'is_typing' => 'required|boolean',
        ]);

        // TEST LOGGING - Add this before broadcast
        Log::info('About to broadcast UserTyping event', [
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'conversation_id' => $request->conversation_id,
            'is_typing' => $request->is_typing
        ]);

        // Broadcast to the receiver
        broadcast(new UserTyping(
            auth()->id(),
            $request->conversation_id,
            $request->is_typing
        ));

        // TEST LOGGING - Add this after broadcast
        Log::info('Broadcast completed');

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator sent'
        ]);
    }
}
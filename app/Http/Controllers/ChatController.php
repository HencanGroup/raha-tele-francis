<?php

namespace App\Http\Controllers;

use App\Events\ConversationCreated;
use App\Events\MessageRead;
use App\Events\NewMessage;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     */
    public function index()
    {
        return inertia('Backend/Chat/Index', [
            'conversations' => $this->getConversations(),
        ]);
    }

    /**
     * Show a specific conversation
     */
    public function show(Conversation $conversation)
    {
        $user = Auth::user();

        // Check if user is part of this conversation
        $this->authorizeConversationAccess($conversation, $user);

        $otherUser = $this->getOtherUser($conversation, $user);

        // Mark messages as read and broadcast
        $this->markMessagesAsRead($conversation, $user);

        // Get visible messages for this user
        $messages = $this->getConversationMessages($conversation, $user);

        return inertia('Backend/Chat/Show', [
            'conversation' => $this->formatConversationForShow($conversation, $otherUser, $user),
            'messages' => $messages,
            'conversations' => $this->getConversations(),
        ]);
    }

    public function getUsers(Request $request)
    {
        return User::all();
    }

    /**
     * Start a new conversation or get existing one
     */
    public function startConversation(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $otherUserId = $validated['user_id'];

        // Prevent chatting with self
        if ($user->id === $otherUserId) {
            return back()->with('error', 'You cannot start a conversation with yourself.');
        }

        // Check if conversation already exists
        $conversation = Conversation::between($user->id, $otherUserId)->first();

        if (! $conversation) {
            // Create new conversation
            $conversation = Conversation::create([
                'user_one_id' => $user->id,
                'user_two_id' => $otherUserId,
                'last_message_at' => now(),
            ]);

            // Broadcast new conversation event
            broadcast(new ConversationCreated($conversation, $user->id))->toOthers();
        }

        return redirect()->route('chat.show', $conversation->id);
    }

    /**
     * Send a new message
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required_without:attachment|string|nullable',
            'type' => 'sometimes|in:text,image,video,audio,file',
            'client_id' => 'sometimes|string',
            'reply_to_id' => 'sometimes|exists:messages,id',
        ]);

        $user = Auth::user();
        $conversation = Conversation::findOrFail($validated['conversation_id']);

        // Check if user is part of this conversation
        $this->authorizeConversationAccess($conversation, $user);

        // Check if conversation is blocked
        if ($conversation->isBlockedForUser($user->id)) {
            return response()->json(['error' => 'This conversation is blocked'], 403);
        }

        $receiverId = $this->getReceiverId($conversation, $user);

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'client_id' => $validated['client_id'] ?? null,
            'message' => $validated['message'] ?? null,
            'type' => $validated['type'] ?? 'text',
            'reply_to_id' => $validated['reply_to_id'] ?? null,
            'is_sent' => true,
            'sent_at' => now(),
        ]);

        // Update conversation last message timestamp
        $conversation->updateQuietly([
            'last_message_at' => now(),
        ]);

        // Broadcast the message
        broadcast(new NewMessage($message, $conversation))->toOthers();

        // Load relationships for response
        $message->load(['sender', 'receiver']);

        return response()->json($this->formatMessageResponse($message, $user));
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        // Check if user is part of this conversation
        $this->authorizeConversationAccess($conversation, $user);

        $messageIds = $this->markMessagesAsRead($conversation, $user);

        return response()->json([
            'success' => true,
            'message_ids' => $messageIds,
        ]);
    }

    /**
     * Handle typing indicator
     */
    public function typing(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        $user = Auth::user();

        // Check if user is part of this conversation
        $this->authorizeConversationAccess($conversation, $user);

        broadcast(new UserTyping($conversation, $user, $validated['is_typing']))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Archive/unarchive conversation for user
     */
    public function toggleArchive(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->user_one_id === $user->id) {
            $conversation->updateQuietly([
                'user_one_archived' => ! $conversation->user_one_archived,
            ]);
        } elseif ($conversation->user_two_id === $user->id) {
            $conversation->updateQuietly([
                'user_two_archived' => ! $conversation->user_two_archived,
            ]);
        } else {
            abort(403);
        }

        return back()->with('success', 'Conversation archived status updated');
    }

    /**
     * Mute/unmute conversation for user
     */
    public function toggleMute(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->user_one_id === $user->id) {
            $conversation->updateQuietly([
                'user_one_muted' => ! $conversation->user_one_muted,
            ]);
        } elseif ($conversation->user_two_id === $user->id) {
            $conversation->updateQuietly([
                'user_two_muted' => ! $conversation->user_two_muted,
            ]);
        } else {
            abort(403);
        }

        return back()->with('success', 'Conversation mute status updated');
    }

    /**
     * Block/unblock user in conversation
     */
    public function toggleBlock(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->user_one_id === $user->id) {
            $conversation->updateQuietly([
                'user_two_blocked' => ! $conversation->user_two_blocked,
            ]);
        } elseif ($conversation->user_two_id === $user->id) {
            $conversation->updateQuietly([
                'user_one_blocked' => ! $conversation->user_one_blocked,
            ]);
        } else {
            abort(403);
        }

        return back()->with('success', 'User block status updated');
    }

    /**
     * Delete conversation (soft delete messages for user)
     */
    public function destroy(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->user_one_id === $user->id) {
            // Soft delete all messages for user one
            $conversation->messages()->update([
                'user_one_deleted' => true,
                'user_one_deleted_at' => now(),
            ]);
            $conversation->updateQuietly(['user_one_archived' => true]);
        } elseif ($conversation->user_two_id === $user->id) {
            // Soft delete all messages for user two
            $conversation->messages()->update([
                'user_two_deleted' => true,
                'user_two_deleted_at' => now(),
            ]);
            $conversation->updateQuietly(['user_two_archived' => true]);
        } else {
            abort(403);
        }

        return redirect()->route('chat.index')->with('success', 'Conversation deleted');
    }

    /**
     * Get formatted conversations for the authenticated user
     */
    protected function getConversations()
    {
        $user = Auth::user();

        return Conversation::where(function (Builder $query) use ($user) {
            $query->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id);
        })
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(fn ($conversation) => $this->formatConversationForList($conversation, $user));
    }

    /**
     * Format a single conversation for the list view
     */
    protected function formatConversationForList(Conversation $conversation, $user): array
    {
        $otherUser = $this->getOtherUser($conversation, $user);
        $lastMessage = $conversation->latestMessage;
        $unreadCount = $this->getUnreadCount($conversation, $user);

        return [
            'id' => $conversation->id,
            'other_user' => $this->formatUserData($otherUser),
            'last_message' => $lastMessage ? $this->formatLastMessageData($lastMessage) : null,
            'unread_count' => $unreadCount,
            'is_muted' => $conversation->isMutedForUser($user->id),
            'is_archived' => $conversation->isArchivedForUser($user->id),
            'is_blocked' => $conversation->isBlockedForUser($user->id),
            'last_message_at' => $conversation->last_message_at,
            'created_at' => $conversation->created_at,
        ];
    }

    /**
     * Format conversation for show view
     */
    protected function formatConversationForShow(Conversation $conversation, $otherUser, $user): array
    {
        return [
            'id' => $conversation->id,
            'other_user' => $this->formatUserData($otherUser),
            'is_muted' => $conversation->isMutedForUser($user->id),
            'is_archived' => $conversation->isArchivedForUser($user->id),
            'is_blocked' => $conversation->isBlockedForUser($user->id),
        ];
    }

    /**
     * Format user data consistently
     */
    protected function formatUserData($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'display_name' => $user->display_name,
            'gender' => $user->gender ?? null,
            'profile_photo_url' => $user->profile_photo_url,
            'is_online' => $user->is_online ?? false,
            'last_seen' => $user->last_seen,
        ];
    }

    /**
     * Format last message data
     */
    protected function formatLastMessageData(Message $message): array
    {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'type' => $message->type,
            'created_at' => $message->created_at,
            'sender_id' => $message->sender_id,
            'is_read' => $message->is_read,
        ];
    }

    /**
     * Format message response for API
     */
    protected function formatMessageResponse(Message $message, $user): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'message' => $message->message,
            'type' => $message->type,
            'sender' => $this->formatUserData($message->sender),
            'receiver_id' => $message->receiver_id,
            'created_at' => $message->created_at,
            'is_read' => $message->is_read,
            'is_mine' => true,
            'client_id' => $message->client_id,
        ];
    }

    /**
     * Format message for display
     */
    protected function formatMessageForDisplay(Message $message, $user): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'message' => $message->message,
            'type' => $message->type,
            'sender' => $this->formatUserData($message->sender),
            'receiver_id' => $message->receiver_id,
            'created_at' => $message->created_at,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at,
            'is_delivered' => $message->is_delivered,
            'is_sent' => $message->is_sent,
            'attachments' => $this->formatAttachments($message),
            'reactions' => $message->reactions,
            'is_mine' => $message->sender_id === $user->id,
            'client_id' => $message->client_id,
        ];
    }

    /**
     * Format attachments if present
     */
    protected function formatAttachments(Message $message): ?array
    {
        if (! $message->isMedia()) {
            return null;
        }

        return [
            'path' => $message->attachment_path,
            'name' => $message->attachment_name,
            'size' => $message->attachment_size,
            'mime' => $message->attachment_mime,
            'meta' => $message->attachment_meta,
        ];
    }

    /**
     * Get the other user in a conversation
     */
    protected function getOtherUser(Conversation $conversation, $user)
    {
        return $conversation->user_one_id === $user->id
            ? $conversation->userTwo
            : $conversation->userOne;
    }

    /**
     * Get receiver ID for a new message
     */
    protected function getReceiverId(Conversation $conversation, $user): int
    {
        return $conversation->user_one_id === $user->id
            ? $conversation->user_two_id
            : $conversation->user_one_id;
    }

    /**
     * Get unread count for a conversation
     */
    protected function getUnreadCount(Conversation $conversation, $user): int
    {
        return $conversation->messages()
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get conversation messages
     */
    protected function getConversationMessages(Conversation $conversation, $user)
    {
        return $conversation->visibleMessagesForUser($user->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($message) => $this->formatMessageForDisplay($message, $user));
    }

    /**
     * Mark messages as read and broadcast
     */
    protected function markMessagesAsRead(Conversation $conversation, $user): array
    {
        $unreadMessages = $conversation->messages()
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->get();

        if ($unreadMessages->isEmpty()) {
            return [];
        }

        $messageIds = $unreadMessages->pluck('id')->toArray();

        DB::transaction(function () use ($messageIds, $conversation, $user) {
            // Bulk update for better performance
            Message::whereIn('id', $messageIds)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            // Update last read timestamp
            if ($conversation->user_one_id === $user->id) {
                $conversation->updateQuietly(['user_one_last_read_at' => now()]);
            } else {
                $conversation->updateQuietly(['user_two_last_read_at' => now()]);
            }
        });

        // Broadcast read receipts
        broadcast(new MessageRead($conversation, $user, $messageIds))->toOthers();

        return $messageIds;
    }

    /**
     * Authorize conversation access
     */
    protected function authorizeConversationAccess(Conversation $conversation, $user): void
    {
        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            abort(403, 'Unauthorized access to conversation');
        }
    }
}

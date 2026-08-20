import React, { useEffect, useRef, useState, useCallback } from "react";
import { Head, usePage } from "@inertiajs/react";
import axios from "axios";
import ChatLayout from "@/Layouts/ChatLayout";
import MessageList from "@/Components/Chat/MessageList";
import MessageInput from "@/Components/Chat/MessageInput";
import ChatHeader from "@/Components/Chat/ChatHeader";
import { Alert } from "react-bootstrap";
import { ChatProvider, useChat } from "@/Components/Contexts/ChatContext";
import {
    normalizeMessage,
    mergeMessages,
    sendApiMessage,
    fetchHistory,
    unlockMessage,
    addReaction,
    removeReaction,
    previewAttachment,
    typeFromMime,
} from "@/Utils/chat";

// Custom hook for Echo events on the open conversation channel
const useEchoEvents = (conversationId, authUser, handlers) => {
    const { onMessageRead, onUserTyping, onNewMessage, onReactionUpdated } =
        handlers;

    useEffect(() => {
        if (!conversationId || !window.Echo) return;

        const channel = window.Echo.private(`conversation.${conversationId}`);

        channel.listen(".messages.read", (e) => {
            if (e.user_id !== authUser.id) {
                onMessageRead(e);
            }
        });

        channel.listen(".user.typing", (e) => {
            if (e.user_id !== authUser.id) {
                onUserTyping(e);
            }
        });

        channel.listen(".new.message", (e) => {
            if (e.sender_id !== authUser.id) {
                onNewMessage(e);
            }
        });

        channel.listen(".message.reaction", onReactionUpdated);

        return () => {
            channel.stopListening(".messages.read");
            channel.stopListening(".user.typing");
            channel.stopListening(".new.message");
            channel.stopListening(".message.reaction");
            window.Echo.leave(`conversation.${conversationId}`);
        };
    }, [
        conversationId,
        authUser.id,
        onMessageRead,
        onUserTyping,
        onNewMessage,
        onReactionUpdated,
    ]);
};

// Custom hook for message sending via POST /api/chat/messages
const useMessageSender = (conversation, authUser, onMessageSent, onError) => {
    const [isSending, setIsSending] = useState(false);
    const [error, setError] = useState(null);

    const sendMessage = useCallback(
        async (message, type = "text", replyTo = null, attachment = null) => {
            setIsSending(true);
            setError(null);

            const clientId = `${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;

            // Optimistic message so the UI feels instant; replaced by the
            // real API response on success or removed on failure.
            const tempMessage = {
                id: `temp-${clientId}`,
                conversation_id: conversation.id,
                message: message ?? "",
                type: attachment ? typeFromMime(attachment.type) : type,
                sender: {
                    id: authUser.id,
                    name: authUser.name,
                    profile_photo_url: authUser.profile_photo_url,
                },
                receiver_id: conversation.other_user.id,
                created_at: new Date().toISOString(),
                is_read: false,
                is_mine: true,
                client_id: clientId,
                is_temp: true,
                attachments: attachment
                    ? previewAttachment(attachment)
                    : null,
            };

            // Add optimistic message
            onMessageSent(tempMessage);

            try {
                const { data } = await sendApiMessage(
                    {
                        conversation_id: conversation.id,
                        message: message ?? "",
                        // Only derive the type from the MIME when a file is attached — for plain
                        // text messages send "text", never "file" (typeFromMime
                        // maps an empty mime to "file", which would make the
                        // server treat a text message as an attachment).
                        type: attachment
                            ? typeFromMime(attachment.type)
                            : (type || "text"),
                        client_id: clientId,
                        reply_to_id: replyTo?.id,
                    },
                    attachment,
                    authUser.id,
                );

                // Replace temp message with the real, normalised one
                onMessageSent(
                    normalizeMessage(data.data, {
                        authUser,
                        otherUser: conversation.other_user,
                    }),
                    clientId,
                );
            } catch (err) {
                const msg =
                    err.response?.data?.message || "Failed to send message";
                setError(msg);
                onError?.(msg);
                // Remove temp message on error
                onMessageSent(null, clientId, true);
            } finally {
                setIsSending(false);
            }
        },
        [conversation, authUser, onMessageSent, onError],
    );

    return { sendMessage, isSending, error, clearError: () => setError(null) };
};

function ChatContent({
    conversation: initialConversation,
    messages: initialMessages,
}) {
    const { auth } = usePage().props;
    const { setActiveConversation, setMessages } = useChat();
    const [conversation] = useState(initialConversation);
    const [localMessages, setLocalMessages] = useState(
        () =>
            (initialMessages || []).map((m) =>
                normalizeMessage(m, {
                    authUser: auth.user,
                    otherUser: conversation.other_user,
                }),
            ),
    );
    const [typingUsers, setTypingUsers] = useState(new Set());
    const [connectionError, setConnectionError] = useState(null);
    const [pagination, setPagination] = useState({
        hasMore: false,
        nextPage: 2,
        loadingOlder: false,
    });
    const [unlockingIds, setUnlockingIds] = useState([]);
    const [actionError, setActionError] = useState(null);

    const messagesEndRef = useRef(null);

    // Scroll to bottom
    const scrollToBottom = useCallback((behavior = "smooth") => {
        messagesEndRef.current?.scrollIntoView({ behavior });
    }, []);

    // Handle message updates
    const handleMessageUpdate = useCallback(
        (newMessage, tempClientId = null, isError = false) => {
            setLocalMessages((prev) => {
                if (isError && tempClientId) {
                    return prev.filter(
                        (msg) => msg.id !== `temp-${tempClientId}`,
                    );
                }

                if (tempClientId) {
                    return prev.map((msg) =>
                        msg.id === `temp-${tempClientId}` ? newMessage : msg,
                    );
                }

                return mergeMessages(prev, [newMessage]);
            });

            if (!isError) {
                setTimeout(scrollToBottom, 100);
            }
        },
        [scrollToBottom],
    );

    // Use message sender hook
    const { sendMessage, isSending, error: sendError, clearError: clearSendError } =
        useMessageSender(
            conversation,
            auth.user,
            handleMessageUpdate,
            (msg) => setActionError(msg),
        );

    // Echo event handlers
    const handleMessageRead = useCallback((e) => {
        setLocalMessages((prev) =>
            prev.map((msg) =>
                e.message_ids.includes(msg.id)
                    ? { ...msg, is_read: true, read_at: e.read_at }
                    : msg,
            ),
        );
    }, []);

    const handleUserTyping = useCallback((e) => {
        setTypingUsers((prev) => {
            const newSet = new Set(prev);
            if (e.is_typing) {
                newSet.add(e.user_id);
            } else {
                newSet.delete(e.user_id);
            }
            return newSet;
        });
    }, []);

    const handleNewMessage = useCallback(
        (e) => {
            setLocalMessages((prev) => {
                const exists = prev.some((msg) => msg.id === e.id);
                if (exists) return prev;
                return mergeMessages(
                    prev,
                    [normalizeMessage(e, {
                        authUser: auth.user,
                        otherUser: conversation.other_user,
                    })],
                );
            });
            setTimeout(scrollToBottom, 100);
        },
        [auth.user, conversation.other_user, scrollToBottom],
    );

    const handleReactionUpdated = useCallback((e) => {
        setLocalMessages((prev) =>
            prev.map((msg) =>
                msg.id === e.message_id
                    ? { ...msg, reactions: e.reactions }
                    : msg,
            ),
        );
    }, []);

    // Set up Echo events
    useEchoEvents(conversation.id, auth.user, {
        onMessageRead: handleMessageRead,
        onUserTyping: handleUserTyping,
        onNewMessage: handleNewMessage,
        onReactionUpdated: handleReactionUpdated,
    });

    // Load the newest history page from the API on mount, merging it with the
    // server-rendered props so pagination state is always established.
    useEffect(() => {
        let cancelled = false;

        const initial = (initialMessages || []).map((m) =>
            normalizeMessage(m, {
                authUser: auth.user,
                otherUser: conversation.other_user,
            }),
        );

        setLocalMessages(initial);
        // The API pages history 50 at a time — if the server props already
        // reach that many, older pages may exist, so offer "Load older".
        setPagination({
            hasMore: initial.length >= 50,
            nextPage: 2,
            loadingOlder: false,
        });

        fetchHistory(conversation.id, 1, auth.user.id)
            .then((res) => {
                if (cancelled) return;
                const items = (res.data || []).map((m) =>
                    normalizeMessage(m, {
                        authUser: auth.user,
                        otherUser: conversation.other_user,
                    }),
                );
                // Replace (not merge into) the server-rendered props with the
                // authoritative API page — stale props from a previous fetch
                // or a wrong query must never survive a conversation switch.
                setLocalMessages(mergeMessages([], items));
                setPagination({
                    hasMore: res.meta.current_page < res.meta.last_page,
                    nextPage: res.meta.current_page + 1,
                    loadingOlder: false,
                });
            })
            .catch(() => {
                // Fall back to the server props if the API is unreachable.
            });

        return () => {
            cancelled = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [conversation.id]);

    // Load an older page of history and prepend it (API pages are newest first,
    // mergeMessages keeps the list sorted oldest→newest).
    const handleLoadOlder = useCallback(async () => {
        if (pagination.loadingOlder || !pagination.hasMore) return;

        setPagination((p) => ({ ...p, loadingOlder: true }));
        try {
            const res = await fetchHistory(conversation.id, pagination.nextPage, auth.user.id);
            const items = (res.data || []).map((m) =>
                normalizeMessage(m, {
                    authUser: auth.user,
                    otherUser: conversation.other_user,
                }),
            );
            setLocalMessages((prev) => mergeMessages(prev, items));
            setPagination({
                hasMore: res.meta.current_page < res.meta.last_page,
                nextPage: res.meta.current_page + 1,
                loadingOlder: false,
            });
        } catch (err) {
            setPagination((p) => ({ ...p, loadingOlder: false }));
        }
    }, [
        conversation.id,
        pagination.loadingOlder,
        pagination.hasMore,
        pagination.nextPage,
        auth.user,
        conversation.other_user,
    ]);

    // Unlock a locked message — the member pays credits via the API.
    const handleUnlock = useCallback(
        async (message) => {
            if (unlockingIds.includes(message.id)) return;

            setUnlockingIds((prev) => [...prev, message.id]);
            setActionError(null);

            try {
                const unlocked = await unlockMessage(message.id, auth.user.id);
                setLocalMessages((prev) =>
                    prev.map((m) =>
                        m.id === unlocked.id
                            ? normalizeMessage(unlocked, {
                                  authUser: auth.user,
                                  otherUser: conversation.other_user,
                              })
                            : m,
                    ),
                );
            } catch (err) {
                setActionError(
                    err.response?.data?.message || "Failed to unlock message",
                );
            } finally {
                setUnlockingIds((prev) =>
                    prev.filter((id) => id !== message.id),
                );
            }
        },
        [unlockingIds, auth.user, conversation.other_user],
    );

    // Add/overwrite or remove the current user's reaction.
    const handleToggleReaction = useCallback(
        async (message, emoji) => {
            const reactions = { ...(message.reactions || {}) };
            const mine = reactions[auth.user.id];

            try {
                if (mine === emoji) {
                    await removeReaction(message.id, auth.user.id);
                    delete reactions[auth.user.id];
                } else {
                    await addReaction(message.id, emoji, auth.user.id);
                    reactions[auth.user.id] = emoji;
                }

                setLocalMessages((prev) =>
                    prev.map((m) =>
                        m.id === message.id ? { ...m, reactions } : m,
                    ),
                );
            } catch (err) {
                console.error("Failed to update reaction:", err);
            }
        },
        [auth.user.id],
    );

    // Set active conversation and messages
    useEffect(() => {
        setActiveConversation(conversation);
        setMessages(localMessages);
        scrollToBottom("auto");
    }, [
        conversation,
        localMessages,
        setActiveConversation,
        setMessages,
        scrollToBottom,
    ]);

    // Mark messages as read (session route — no API equivalent exists)
    const handleMarkAsRead = useCallback(async () => {
        try {
            await axios.post(`/chat/${conversation.id}/read`);
        } catch (err) {
            console.error("Failed to mark messages as read:", err);
        }
    }, [conversation.id]);

    // Handle typing (session route — no API equivalent exists)
    const handleTyping = useCallback(
        (isTyping) => {
            axios
                .post(`/chat/${conversation.id}/typing`, {
                    is_typing: isTyping,
                })
                .catch((err) =>
                    console.error("Failed to send typing status:", err),
                );
        },
        [conversation.id],
    );

    // Check connection status
    useEffect(() => {
        const handleOnline = () => setConnectionError(null);
        const handleOffline = () => setConnectionError("You are offline");

        window.addEventListener("online", handleOnline);
        window.addEventListener("offline", handleOffline);

        return () => {
            window.removeEventListener("online", handleOnline);
            window.removeEventListener("offline", handleOffline);
        };
    }, []);

    return (
        <div className="d-flex flex-column h-100">
            {connectionError && (
                <Alert variant="warning" className="m-2 rounded">
                    ⚠️ {connectionError}
                </Alert>
            )}

            <ChatHeader conversation={conversation} typingUsers={typingUsers} />

            <MessageList
                messages={localMessages}
                conversation={conversation}
                onMarkAsRead={handleMarkAsRead}
                messagesEndRef={messagesEndRef}
                typingUsers={typingUsers}
                onUnlock={handleUnlock}
                unlockingIds={unlockingIds}
                onToggleReaction={handleToggleReaction}
                onLoadOlder={handleLoadOlder}
                hasMore={pagination.hasMore}
                loadingOlder={pagination.loadingOlder}
            />

            {sendError && (
                <Alert
                    variant="danger"
                    className="mx-3 mb-0 rounded"
                    dismissible
                    onClose={clearSendError}
                >
                    {sendError}
                </Alert>
            )}

            {actionError && (
                <Alert
                    variant="danger"
                    className="mx-3 mb-0 rounded"
                    dismissible
                    onClose={() => setActionError(null)}
                >
                    {actionError}
                </Alert>
            )}

            <MessageInput
                onSendMessage={sendMessage}
                onTyping={handleTyping}
                disabled={conversation.is_blocked || !navigator.onLine}
                isSending={isSending}
            />
        </div>
    );
}

// Memoize ChatContent
const MemoizedChatContent = React.memo(ChatContent);

export default function Show({ conversation, messages, conversations, archivedCount }) {
    const { auth } = usePage().props;

    return (
        <ChatLayout conversations={conversations} archivedCount={archivedCount}>
            <Head title={`Chat with ${conversation.other_user.name}`} />

            <ChatProvider auth={auth} conversations={conversations}>
                <MemoizedChatContent
                    conversation={conversation}
                    messages={messages}
                />
            </ChatProvider>
        </ChatLayout>
    );
}
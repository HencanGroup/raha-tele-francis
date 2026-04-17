import React, { useEffect, useRef, useState, useCallback } from "react";
import { Head, usePage } from "@inertiajs/react";
import ChatLayout from "@/Layouts/ChatLayout";
import MessageList from "@/Components/Chat/MessageList";
import MessageInput from "@/Components/Chat/MessageInput";
import ChatHeader from "@/Components/Chat/ChatHeader";
import { Alert } from "react-bootstrap";
import axios from "axios";
import { ChatProvider, useChat } from "@/Components/Contexts/ChatContext";

// Custom hook for Echo events
const useEchoEvents = (
    conversationId,
    authUser,
    onMessageRead,
    onUserTyping,
    onNewMessage,
) => {
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

        return () => {
            channel.stopListening(".messages.read");
            channel.stopListening(".user.typing");
            channel.stopListening(".new.message");
            window.Echo.leave(`conversation.${conversationId}`);
        };
    }, [conversationId, authUser.id, onMessageRead, onUserTyping, onNewMessage]);
};

// Custom hook for message sending
const useMessageSender = (conversation, authUser, onMessageSent) => {
    const [isSending, setIsSending] = useState(false);
    const [error, setError] = useState(null);

    const sendMessage = useCallback(
        async (message, type = "text", replyTo = null) => {
            setIsSending(true);
            setError(null);

            const clientId = `${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;

            // Optimistic message
            const tempMessage = {
                id: `temp-${clientId}`,
                conversation_id: conversation.id,
                message,
                type,
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
            };

            // Add optimistic message
            onMessageSent(tempMessage);

            try {
                const response = await axios.post("/chat/send", {
                    conversation_id: conversation.id,
                    message,
                    type,
                    client_id: clientId,
                    reply_to_id: replyTo?.id,
                });

                // Replace temp message with real one
                onMessageSent(response.data, clientId);
            } catch (err) {
                setError(err.response?.data?.error || "Failed to send message");
                // Remove temp message on error
                onMessageSent(null, clientId, true);
            } finally {
                setIsSending(false);
            }
        },
        [conversation, authUser, onMessageSent],
    );

    return { sendMessage, isSending, error };
};

function ChatContent({
    conversation: initialConversation,
    messages: initialMessages,
}) {
    const { auth } = usePage().props;
    const { setActiveConversation, setMessages } = useChat();
    const [conversation] = useState(initialConversation);
    const [localMessages, setLocalMessages] = useState(initialMessages);
    const [typingUsers, setTypingUsers] = useState(new Set());
    const [connectionError, setConnectionError] = useState(null);

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

                return [...prev, newMessage];
            });

            if (!isError) {
                setTimeout(scrollToBottom, 100);
            }
        },
        [scrollToBottom],
    );

    // Use message sender hook
    const {
        sendMessage,
        isSending,
        error: sendError,
    } = useMessageSender(conversation, auth.user, handleMessageUpdate);

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

    const handleNewMessage = useCallback((e) => {
        setLocalMessages((prev) => {
            const exists = prev.some((msg) => msg.id === e.id);
            if (exists) return prev;
            return [...prev, e];
        });
        setTimeout(scrollToBottom, 100);
    }, [scrollToBottom]);

    // Set up Echo events
    useEchoEvents(
        conversation.id,
        auth.user,
        handleMessageRead,
        handleUserTyping,
        handleNewMessage,
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

    // Reset localMessages when conversation changes
    useEffect(() => {
        setLocalMessages(initialMessages);
    }, [conversation.id, initialMessages]);

    // Mark messages as read
    const handleMarkAsRead = useCallback(async () => {
        try {
            await axios.post(`/chat/${conversation.id}/read`);
        } catch (err) {
            console.error("Failed to mark messages as read:", err);
        }
    }, [conversation.id]);

    // Handle typing
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
            />

            {sendError && (
                <Alert
                    variant="danger"
                    className="mx-3 mb-0 rounded"
                    dismissible
                >
                    {sendError}
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

export default function Show({ conversation, messages, conversations }) {
    return (
        <ChatLayout conversations={conversations}>
            <Head title={`Chat with ${conversation.other_user.name}`} />

            <ChatProvider>
                <MemoizedChatContent
                    conversation={conversation}
                    messages={messages}
                />
            </ChatProvider>
        </ChatLayout>
    );
}

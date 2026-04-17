import React, {
    createContext,
    useContext,
    useEffect,
    useState,
    useCallback,
} from "react";
import { router } from "@inertiajs/react";
import axios from "axios";

const ChatContext = createContext();

export function ChatProvider({ children, auth }) {
    const [activeConversation, setActiveConversation] = useState(null);
    const [conversations, setConversations] = useState([]);
    const [messages, setMessages] = useState([]);
    const [typingUsers, setTypingUsers] = useState({});
    const [unreadCount, setUnreadCount] = useState(0);
    const [isConnected, setIsConnected] = useState(false);

    /******************************************************
     * 🔌 ECHO CONNECTION SETUP
     ******************************************************/
    useEffect(() => {
        if (!auth?.user) return;

        console.log("Setting up Echo connection for user:", auth.user.id);

        // Mark as connected
        setIsConnected(true);

        // Update heartbeat every 30 seconds
        const heartbeatInterval = setInterval(() => {
            axios
                .get("/heartbeat")
                .catch((err) => console.warn("Heartbeat failed:", err));
        }, 30000);

        /******************************************************
         * 📨 PRIVATE CHANNEL - USER SPECIFIC
         ******************************************************/
        const userChannel = Echo.private(`user.${auth.user.id}`);

        // Listen for new messages
        userChannel.listen(".new.message", (e) => {
            console.log("New message received:", e);
            handleNewMessage(e);
        });

        // Listen for new conversations
        userChannel.listen(".new.conversation", (e) => {
            console.log("New conversation received:", e);
            handleNewConversation(e);
        });

        /******************************************************
         * 💬 CONVERSATION CHANNELS
         ******************************************************/
        // This will be set up when a conversation becomes active
        let conversationChannel = null;

        if (activeConversation) {
            setupConversationChannel(activeConversation.id);
        }

        /******************************************************
         * 🧹 CLEANUP
         ******************************************************/
        return () => {
            clearInterval(heartbeatInterval);

            if (userChannel) {
                userChannel.stopListening(".new.message");
                userChannel.stopListening(".new.conversation");
                Echo.leave(`user.${auth.user.id}`);
            }

            if (conversationChannel) {
                conversationChannel.stopListening(".messages.read");
                conversationChannel.stopListening(".user.typing");
                Echo.leave(`conversation.${activeConversation?.id}`);
            }

            setIsConnected(false);
        };
    }, [auth?.user, activeConversation?.id]);

    /******************************************************
     * 🔧 CONVERSATION CHANNEL SETUP
     ******************************************************/
    const setupConversationChannel = useCallback(
        (conversationId) => {
            if (!conversationId) return null;

            const channel = Echo.private(`conversation.${conversationId}`);

            // Listen for read receipts
            channel.listen(".messages.read", (e) => {
                console.log("Messages read:", e);
                if (e.user_id !== auth?.user?.id) {
                    setMessages((prev) =>
                        prev.map((msg) =>
                            e.message_ids.includes(msg.id)
                                ? { ...msg, is_read: true, read_at: e.read_at }
                                : msg,
                        ),
                    );
                }
            });

            // Listen for typing indicators
            channel.listen(".user.typing", (e) => {
                console.log("User typing:", e);
                if (e.user_id !== auth?.user?.id) {
                    setTypingUsers((prev) => ({
                        ...prev,
                        [e.user_id]: e.is_typing,
                    }));

                    // Clear typing indicator after 3 seconds if no update
                    if (e.is_typing) {
                        setTimeout(() => {
                            setTypingUsers((prev) => ({
                                ...prev,
                                [e.user_id]: false,
                            }));
                        }, 3000);
                    }
                }
            });

            return channel;
        },
        [auth?.user?.id],
    );

    /******************************************************
     * 📥 HANDLE NEW MESSAGE
     ******************************************************/
    const handleNewMessage = useCallback(
        (e) => {
            // Update conversations list
            setConversations((prev) => {
                const existing = prev.find((c) => c.id === e.conversation_id);

                if (existing) {
                    return prev.map((c) =>
                        c.id === e.conversation_id
                            ? {
                                  ...c,
                                  last_message: e,
                                  unread_count:
                                      c.unread_count +
                                      (c.id === activeConversation?.id ? 0 : 1),
                              }
                            : c,
                    );
                } else {
                    // Fetch new conversation if not in list
                    router.reload({ only: ["conversations"] });
                }
                return prev;
            });

            // Add message to active conversation if open
            if (activeConversation?.id === e.conversation_id) {
                setMessages((prev) => [...prev, e]);

                // Mark as read if it's the active conversation
                if (e.receiver_id === auth?.user?.id) {
                    markMessagesAsRead(e.conversation_id, [e.id]);
                }
            } else {
                // Update unread count for notification
                setUnreadCount((prev) => prev + 1);
            }
        },
        [activeConversation?.id, auth?.user?.id],
    );

    /******************************************************
     * 📤 MARK MESSAGES AS READ
     ******************************************************/
    const markMessagesAsRead = async (conversationId, messageIds) => {
        try {
            await axios.post(`/chat/${conversationId}/messages/read`, {
                message_ids: messageIds,
            });
        } catch (error) {
            console.error("Failed to mark messages as read:", error);
        }
    };

    /******************************************************
     * 📥 HANDLE NEW CONVERSATION
     ******************************************************/
    const handleNewConversation = useCallback(
        (e) => {
            setConversations((prev) => {
                const existing = prev.find((c) => c.id === e.id);
                if (existing) return prev;
                return [...prev, e];
            });
        },
        [],
    );

    /******************************************************
     * ⌨️ SEND TYPING INDICATOR
     ******************************************************/
    const sendTypingIndicator = useCallback((conversationId, isTyping) => {
        if (!conversationId) return;

        axios
            .post(`/chat/${conversationId}/typing`, { is_typing: isTyping })
            .catch((err) =>
                console.warn("Failed to send typing indicator:", err),
            );
    }, []);

    /******************************************************
     * 🔄 FETCH UNREAD COUNT
     ******************************************************/
    const fetchUnreadCount = useCallback(async () => {
        try {
            const response = await axios.get("/api/conversations/unread-count");
            setUnreadCount(response.data.unread_count);
        } catch (error) {
            console.error("Failed to fetch unread count:", error);
        }
    }, []);

    /******************************************************
     * 🗑️ CLEAR CONVERSATION MESSAGES
     ******************************************************/
    const clearMessages = useCallback(() => {
        setMessages([]);
    }, []);

    /******************************************************
     * 📊 CONTEXT VALUE
     ******************************************************/
    const value = {
        // State
        activeConversation,
        conversations,
        messages,
        typingUsers,
        unreadCount,
        isConnected,

        // Setters
        setActiveConversation,
        setConversations,
        setMessages,

        // Actions
        sendTypingIndicator,
        markMessagesAsRead,
        fetchUnreadCount,
        clearMessages,
        handleNewMessage,
    };

    return (
        <ChatContext.Provider value={value}>{children}</ChatContext.Provider>
    );
}

/******************************************************
 * 🎣 CUSTOM HOOK
 ******************************************************/
export function useChat() {
    const context = useContext(ChatContext);

    if (!context) {
        throw new Error("useChat must be used within a ChatProvider");
    }

    return context;
}

export default ChatContext;

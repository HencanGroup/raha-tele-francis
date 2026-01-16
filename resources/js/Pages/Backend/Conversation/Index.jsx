import AppLayout from "@/Layouts/AppLayout";
import { Head, usePage } from "@inertiajs/react";
import { useEffect, useState, useRef, useCallback } from "react";
import axios from "axios";
import {
    Container,
    Row,
    Col,
    Card,
    ListGroup,
    Badge,
    Form,
    Button,
    Alert,
    Spinner,
    InputGroup,
    Image,
    Dropdown,
    Toast,
    ToastContainer,
} from "react-bootstrap";
import { useDivHeights } from "@/Hooks/useSizes";
import { getProfileImage } from "@/Utils/helpers";
import debounce from "lodash/debounce";
import { CheckCircle, XCircle, Send, MoreVertical } from "lucide-react";
import { useErrorToast } from "@/Hooks/useErrorToast";
import { useTypingDetection } from "@/Hooks/useTypingDetection";

const ChatApp = () => {
    const { auth } = usePage().props;
    const [socketId, setSocketId] = useState(null);
    const [isConnected, setIsConnected] = useState(false);
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState("");
    const [selectedConversation, setSelectedConversation] = useState(null);
    const [conversations, setConversations] = useState([]);
    const [typingUsers, setTypingUsers] = useState({});
    const [isLoading, setIsLoading] = useState(false);
    const [isSending, setIsSending] = useState(false);
    const [connectionAttempts, setConnectionAttempts] = useState(0);
    const [connectionError, setConnectionError] = useState(null);
    const [showToast, setShowToast] = useState(false);
    const [toastMessage, setToastMessage] = useState("");
    const [toastVariant, setToastVariant] = useState("danger");

    const messagesEndRef = useRef(null);
    const echoRef = useRef(null);
    const reconnectTimeoutRef = useRef(null);
    const subscribedChannels = useRef(new Set());
    const navbarHeight = useDivHeights("escort-navbar");
    const { showErrorToast } = useErrorToast();

    // Enhanced typing detection for current user
    const {
        isTyping: isUserTyping,
        inputRef,
        detectTypingActivity,
        handleCompositionStart,
        handleCompositionEnd,
        handleFocus: handleInputFocus,
        handleBlur: handleInputBlur,
        resetTyping: resetUserTyping,
    } = useTypingDetection({
        onTypingStart: () => sendTypingIndicator(true),
        onTypingStop: () => sendTypingIndicator(false),
        idleTimeout: 1500,
        debounceTime: 300,
        minCharsForTyping: 1,
    });

    // Initialize debounced typing send function
    const sendTypingDebounceRef = useRef(
        debounce((isTyping) => sendTypingIndicator(isTyping), 300)
    );

    // Clear typing timeout for other users
    const typingTimeoutsRef = useRef({});

    // Show toast notification
    const showNotification = (message, variant = "danger") => {
        setToastMessage(message);
        setToastVariant(variant);
        setShowToast(true);
    };

    // Fetch conversations
    const fetchConversations = useCallback(async () => {
        try {
            setIsLoading(true);
            const response = await axios.get(route("api.conversations.index"));
            setConversations(response.data);
        } catch (error) {
            console.error("Error fetching conversations:", error);
            showErrorToast(error);
        } finally {
            setIsLoading(false);
        }
    }, [showErrorToast]);

    // Fetch messages for a conversation
    const fetchMessages = useCallback(
        async (conversationId) => {
            try {
                setIsLoading(true);
                const response = await axios.get(
                    route("api.conversations.messages", {
                        conversation: conversationId,
                    })
                );
                setMessages(response.data);
            } catch (error) {
                console.error("Error fetching messages:", error);
                showErrorToast(error);
            } finally {
                setIsLoading(false);
            }
        },
        [showErrorToast]
    );

    // Initialize on mount
    useEffect(() => {
        fetchConversations();

        // Initialize Echo/Pusher connection
        if (window.Echo) {
            initializeEcho();
        } else {
            const checkEchoInterval = setInterval(() => {
                if (window.Echo) {
                    clearInterval(checkEchoInterval);
                    initializeEcho();
                }
            }, 500);
        }

        // Cleanup
        return () => {
            cleanupConnection();
        };
    }, []);

    // Initialize Echo/Pusher
    const initializeEcho = useCallback(() => {
        if (!window.Echo) {
            console.error("Echo is not available");
            setConnectionError("WebSocket service not available");
            scheduleReconnect();
            return;
        }

        echoRef.current = window.Echo;

        // Configure Pusher
        const pusher = echoRef.current.connector.pusher;

        // Enable logging for debugging
        if (process.env.NODE_ENV === "development") {
            pusher.log = (message) => console.log("[Pusher]", message);
        }

        // Connection event handlers
        pusher.connection.bind("connecting", () => {
            console.log("🔄 Connecting to chat service...");
            setConnectionError(null);
        });

        pusher.connection.bind("connected", () => {
            console.log("✅ Connected to chat service");
            const socketId = echoRef.current.socketId();
            setSocketId(socketId);
            setIsConnected(true);
            setConnectionAttempts(0);
            setConnectionError(null);
            joinMyChannel();
        });

        pusher.connection.bind("disconnected", () => {
            console.log("❌ Disconnected from chat service");
            setIsConnected(false);
            subscribedChannels.current.clear();
            clearTypingIndicators();
            scheduleReconnect();
        });

        pusher.connection.bind("error", (error) => {
            console.error("❌ Connection error:", error);
            setConnectionError("Connection error. Please check your network.");
        });

        // Check if already connected
        if (pusher.connection.state === "connected") {
            const socketId = echoRef.current.socketId();
            setSocketId(socketId);
            setIsConnected(true);
            joinMyChannel();
        }
    }, [connectionAttempts]);

    // Schedule reconnection
    const scheduleReconnect = useCallback(() => {
        if (reconnectTimeoutRef.current) {
            clearTimeout(reconnectTimeoutRef.current);
        }

        if (connectionAttempts < 5) {
            reconnectTimeoutRef.current = setTimeout(() => {
                console.log(`🔄 Reconnecting (${connectionAttempts + 1}/5)`);
                setConnectionAttempts((prev) => prev + 1);
                initializeEcho();
            }, 3000);
        }
    }, [connectionAttempts, initializeEcho]);

    // Cleanup connection
    const cleanupConnection = useCallback(() => {
        if (reconnectTimeoutRef.current) {
            clearTimeout(reconnectTimeoutRef.current);
        }

        // Clear all typing timeouts
        Object.values(typingTimeoutsRef.current).forEach((timeout) => {
            clearTimeout(timeout);
        });

        // Clear user typing
        if (sendTypingDebounceRef.current) {
            sendTypingDebounceRef.current.cancel();
        }

        resetUserTyping();

        // Disconnect Echo
        if (echoRef.current) {
            try {
                subscribedChannels.current.forEach((channelName) => {
                    echoRef.current.leave(channelName);
                });
                echoRef.current.disconnect();
            } catch (error) {
                console.error("Error disconnecting:", error);
            }
        }
    }, [resetUserTyping]);

    // Join user's private channel
    const joinMyChannel = useCallback(() => {
        if (!echoRef.current || !auth.user?.id) {
            console.error(
                "Cannot join channel: Echo not initialized or user not authenticated"
            );
            return;
        }

        try {
            // Leave existing channels
            subscribedChannels.current.forEach((channelName) => {
                echoRef.current.leave(channelName);
            });
            subscribedChannels.current.clear();

            // Join user's private channel
            const myChannelName = `user.${auth.user.id}`;
            const myChannel = echoRef.current.private(myChannelName);
            subscribedChannels.current.add(myChannelName);

            // Listen for events
            myChannel
                .listen(".MessageSent", handleNewMessage)
                .listen(".typing", handleTypingEvent)
                .listen(".MessageRead", handleMessageRead)
                .listen(".MessageDelivered", handleMessageDelivered);

            myChannel.subscribed(() => {
                console.log(`✅ Subscribed to ${myChannelName}`);
                setConnectionError(null);
            });

            myChannel.error((error) => {
                console.error(`❌ Channel error:`, error);
                setConnectionError("Failed to subscribe to chat channel");
            });
        } catch (error) {
            console.error("❌ Error joining channel:", error);
            setConnectionError("Failed to join chat channel");
        }
    }, [auth.user?.id]);

    // Handle new message
    const handleNewMessage = useCallback(
        (data) => {
            const { message, conversation, sender } = data;

            // Handle our own temp message
            if (message.sender_id === auth.user.id) {
                setMessages((prev) => {
                    const updatedMessages = prev.map((msg) => {
                        if (msg.is_temp && msg.content === message.content) {
                            return { ...message, is_temp: false };
                        }
                        return msg;
                    });

                    const tempExists = prev.some(
                        (msg) => msg.is_temp && msg.content === message.content
                    );

                    if (!tempExists) {
                        return [...prev, message];
                    }

                    return updatedMessages;
                });
            }
            // Handle message from other user
            else {
                // Add to messages if viewing this conversation
                if (
                    selectedConversation &&
                    conversation.id === selectedConversation.id
                ) {
                    setMessages((prev) => {
                        const exists = prev.find((m) => m.id === message.id);
                        if (exists) return prev;
                        return [...prev, message];
                    });

                    // Mark as read
                    markMessageAsRead(message.id);
                }

                // Update conversations
                updateConversationWithNewMessage(conversation, message, sender);
            }
        },
        [selectedConversation, auth.user.id]
    );

    // Handle typing event from other users
    const handleTypingEvent = useCallback(
        (data) => {
            console.log("Typing event received:", data);

            // Only process if we're viewing this conversation
            if (
                selectedConversation &&
                data.user_id !== auth.user.id &&
                data.conversation_id === selectedConversation.id
            ) {
                const userId = data.user_id;
                const isTyping = data.is_typing;

                // Clear existing timeout for this user
                if (typingTimeoutsRef.current[userId]) {
                    clearTimeout(typingTimeoutsRef.current[userId]);
                    delete typingTimeoutsRef.current[userId];
                }

                // Update typing state
                setTypingUsers((prev) => ({
                    ...prev,
                    [userId]: isTyping,
                }));

                // If user is typing, set timeout to clear it
                if (isTyping) {
                    typingTimeoutsRef.current[userId] = setTimeout(() => {
                        setTypingUsers((prev) => ({
                            ...prev,
                            [userId]: false,
                        }));
                        delete typingTimeoutsRef.current[userId];
                    }, 2000);
                }
            }
        },
        [selectedConversation, auth.user.id]
    );

    // Handle message read
    const handleMessageRead = useCallback(
        (data) => {
            if (
                selectedConversation &&
                data.sender_id === selectedConversation.other_user.id
            ) {
                setMessages((prev) =>
                    prev.map((msg) =>
                        msg.sender_id === auth.user.id &&
                        msg.id === data.message_id
                            ? {
                                  ...msg,
                                  read_at:
                                      data.read_at || new Date().toISOString(),
                              }
                            : msg
                    )
                );
            }
        },
        [selectedConversation, auth.user.id]
    );

    // Handle message delivered
    const handleMessageDelivered = useCallback(
        (data) => {
            if (
                selectedConversation &&
                data.sender_id === selectedConversation.other_user.id
            ) {
                setMessages((prev) =>
                    prev.map((msg) =>
                        msg.sender_id === auth.user.id &&
                        msg.id === data.message_id
                            ? {
                                  ...msg,
                                  is_delivered: true,
                                  delivered_at: data.delivered_at,
                              }
                            : msg
                    )
                );
            }
        },
        [selectedConversation, auth.user.id]
    );

    // Update conversation with new message
    const updateConversationWithNewMessage = useCallback(
        (conversation, message, sender) => {
            setConversations((prev) => {
                const existingIndex = prev.findIndex(
                    (c) => c.id === conversation.id
                );

                if (existingIndex > -1) {
                    const updated = [...prev];
                    const existingConv = updated[existingIndex];

                    updated[existingIndex] = {
                        ...existingConv,
                        other_user: sender || existingConv.other_user,
                        last_message: message,
                        last_message_at: message.created_at,
                        unread_count:
                            selectedConversation?.id !== conversation.id
                                ? (existingConv.unread_count || 0) + 1
                                : 0,
                    };

                    // Move to top
                    const [moved] = updated.splice(existingIndex, 1);
                    updated.unshift(moved);

                    return updated;
                } else {
                    // New conversation
                    return [
                        {
                            ...conversation,
                            other_user: sender,
                            last_message: message,
                            last_message_at: message.created_at,
                            unread_count: 1,
                        },
                        ...prev,
                    ];
                }
            });
        },
        [selectedConversation]
    );

    // Mark message as read
    const markMessageAsRead = useCallback(async (messageId) => {
        try {
            await axios.post(
                route("api.messages.mark-as-read", { message: messageId })
            );
        } catch (error) {
            console.error("Error marking message as read:", error);
        }
    }, []);

    // Mark conversation as read
    const markConversationAsRead = useCallback(async (conversationId) => {
        try {
            await axios.post(
                route("api.conversations.mark-as-read", {
                    conversation: conversationId,
                })
            );

            setConversations((prev) =>
                prev.map((conv) =>
                    conv.id === conversationId
                        ? { ...conv, unread_count: 0 }
                        : conv
                )
            );
        } catch (error) {
            console.error("Error marking conversation as read:", error);
        }
    }, []);

    // Send typing indicator
    const sendTypingIndicator = useCallback(
        async (isTyping) => {
            if (!selectedConversation || !auth.user.id || !isConnected) return;

            try {
                await axios.post(route("api.messages.typing"), {
                    receiver_id: selectedConversation.other_user.id,
                    conversation_id: selectedConversation.id,
                    is_typing: isTyping,
                });
            } catch (error) {
                console.error("Error sending typing indicator:", error);
            }
        },
        [selectedConversation, auth.user.id, isConnected]
    );

    // Handle message input with enhanced typing detection
    const handleMessageInput = useCallback(
        (e) => {
            const value = e.target.value;
            setNewMessage(value);

            // Detect typing activity
            detectTypingActivity(e);

            // Auto-resize textarea
            if (inputRef.current) {
                inputRef.current.style.height = "auto";
                inputRef.current.style.height = `${Math.min(
                    inputRef.current.scrollHeight,
                    120
                )}px`;
            }
        },
        [detectTypingActivity]
    );

    // Handle key press for sending
    const handleKeyPress = (e) => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    // Send message
    const sendMessage = async () => {
        if (!newMessage.trim() || !selectedConversation || isSending) return;

        const messageContent = newMessage.trim();

        // Create temporary message
        const tempMessage = {
            id: `temp-${Date.now()}`,
            content: messageContent,
            sender_id: auth.user.id,
            receiver_id: selectedConversation.other_user.id,
            conversation_id: selectedConversation.id,
            created_at: new Date().toISOString(),
            read_at: null,
            is_temp: true,
            is_sending: true,
            is_delivered: false,
        };

        // Add temp message
        setMessages((prev) => [...prev, tempMessage]);
        setNewMessage("");
        setIsSending(true);

        // Reset textarea height
        if (inputRef.current) {
            inputRef.current.style.height = "auto";
        }

        // Stop typing indicator
        resetUserTyping();

        try {
            // Send to server
            const response = await axios.post(route("api.messages.store"), {
                receiver_id: selectedConversation.other_user.id,
                message: messageContent,
                type: "text",
                socket_id: socketId,
            });

            // Update conversation
            setConversations((prev) => {
                const updated = [...prev];
                const convIndex = updated.findIndex(
                    (c) => c.id === selectedConversation.id
                );

                if (convIndex > -1) {
                    updated[convIndex] = {
                        ...updated[convIndex],
                        last_message: response.data,
                        last_message_at: response.data.created_at,
                    };

                    // Move to top
                    const [moved] = updated.splice(convIndex, 1);
                    updated.unshift(moved);
                }

                return updated;
            });
        } catch (error) {
            console.error("❌ Error sending message:", error);

            // Mark as failed
            setMessages((prev) =>
                prev.map((msg) =>
                    msg.id === tempMessage.id
                        ? {
                              ...msg,
                              failed: true,
                              is_sending: false,
                              error: error.message,
                          }
                        : msg
                )
            );

            showNotification(
                `Failed to send message: ${
                    error.response?.data?.message || error.message
                }`,
                "danger"
            );
        } finally {
            setIsSending(false);
        }
    };

    // Select conversation
    const selectConversation = (conversation) => {
        setSelectedConversation(conversation);

        // Clear typing indicators
        clearTypingIndicators();

        // Reset user typing
        resetUserTyping();

        // Clear input
        setNewMessage("");

        // Mark as read in UI
        setConversations((prev) =>
            prev.map((conv) =>
                conv.id === conversation.id
                    ? { ...conv, unread_count: 0 }
                    : conv
            )
        );

        // Fetch messages
        fetchMessages(conversation.id);

        // Send read receipt
        if (conversation.unread_count > 0) {
            markConversationAsRead(conversation.id);
        }
    };

    // Clear all typing indicators
    const clearTypingIndicators = useCallback(() => {
        setTypingUsers({});
        Object.values(typingTimeoutsRef.current).forEach((timeout) => {
            clearTimeout(timeout);
        });
        typingTimeoutsRef.current = {};
    }, []);

    // Manual reconnect
    const handleReconnect = () => {
        cleanupConnection();
        setConnectionAttempts(0);
        setConnectionError(null);
        initializeEcho();
    };

    // Scroll to bottom of messages
    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    // Format time
    const formatTime = (dateString) => {
        if (!dateString) return "";
        const date = new Date(dateString);
        return date.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    // Format date
    const formatDate = (dateString) => {
        if (!dateString) return "";
        const date = new Date(dateString);
        const today = new Date();

        if (date.toDateString() === today.toDateString()) {
            return formatTime(dateString);
        }

        return date.toLocaleDateString([], { month: "short", day: "numeric" });
    };

    // Get last seen text
    const getLastSeenText = (user) => {
        if (user.online) return "Online";
        if (!user.last_seen) return "Never seen";

        const lastSeen = new Date(user.last_seen);
        const diffMinutes = Math.floor((new Date() - lastSeen) / (1000 * 60));

        if (diffMinutes < 1) return "Just now";
        if (diffMinutes < 60) return `${diffMinutes}m ago`;
        if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)}h ago`;
        return `${Math.floor(diffMinutes / 1440)}d ago`;
    };

    return (
        <AppLayout
            showHeaderLinks={false}
            showNavBar={true}
            showFooter={false}
            navBarFluid={true}
        >
            <Head title="Messages" />

            {/* Toast Notifications */}
            <ToastContainer position="top-end" className="p-3">
                <Toast
                    show={showToast}
                    onClose={() => setShowToast(false)}
                    delay={5000}
                    autohide
                    bg={toastVariant}
                >
                    <Toast.Header>
                        <strong className="me-auto">Chat Notification</strong>
                    </Toast.Header>
                    <Toast.Body className="text-white">
                        {toastMessage}
                    </Toast.Body>
                </Toast>
            </ToastContainer>

            <Container
                fluid
                className="bg-dark p-0"
                style={{ height: `calc(100vh - ${navbarHeight}px)` }}
            >
                {/* Connection Status Bar */}
                <Alert
                    variant={isConnected ? "success" : "warning"}
                    className="m-0 rounded-0 text-center py-2"
                >
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            {isConnected ? (
                                <CheckCircle
                                    size={18}
                                    className="me-2 text-success"
                                />
                            ) : (
                                <XCircle
                                    size={18}
                                    className="me-2 text-danger"
                                />
                            )}
                            {isConnected
                                ? `Connected as ${auth.user.name}`
                                : "Connecting to chat service..."}
                            {connectionError && (
                                <span className="ms-2">{connectionError}</span>
                            )}
                        </div>
                        {!isConnected && (
                            <Button
                                variant="outline-light"
                                size="sm"
                                onClick={handleReconnect}
                                disabled={connectionAttempts >= 5}
                            >
                                {connectionAttempts >= 5
                                    ? "Max attempts"
                                    : "Reconnect"}
                            </Button>
                        )}
                    </div>
                </Alert>

                <Row className="g-0 h-100">
                    {/* Conversations Sidebar */}
                    <Col md={3} className="border-end bg-white h-100">
                        <Card className="h-100 border-0 rounded-0">
                            <Card.Header className="bg-primary text-white py-3">
                                <Card.Title className="mb-1">
                                    Messages
                                </Card.Title>
                                <Card.Subtitle className="text-white-50">
                                    {conversations.length} conversations
                                </Card.Subtitle>
                            </Card.Header>
                            <Card.Body className="p-0 overflow-auto">
                                {isLoading ? (
                                    <div className="text-center py-5">
                                        <Spinner animation="border" />
                                    </div>
                                ) : conversations.length === 0 ? (
                                    <div className="text-center py-5">
                                        <p className="text-muted">
                                            No conversations yet
                                        </p>
                                    </div>
                                ) : (
                                    <ListGroup variant="flush">
                                        {conversations.map((conversation) => (
                                            <ListGroup.Item
                                                key={conversation.id}
                                                action
                                                active={
                                                    selectedConversation?.id ===
                                                    conversation.id
                                                }
                                                onClick={() =>
                                                    selectConversation(
                                                        conversation
                                                    )
                                                }
                                                className="border-0 rounded-0 py-3 px-3"
                                            >
                                                <div className="d-flex justify-content-between align-items-center">
                                                    <div className="d-flex align-items-center">
                                                        <div className="position-relative">
                                                            <Image
                                                                src={getProfileImage(
                                                                    conversation.other_user
                                                                )}
                                                                roundedCircle
                                                                width={45}
                                                                height={45}
                                                                className="object-fit-cover"
                                                                alt={
                                                                    conversation
                                                                        .other_user
                                                                        .name
                                                                }
                                                            />
                                                            <Badge
                                                                bg={
                                                                    conversation
                                                                        .other_user
                                                                        .online
                                                                        ? "success"
                                                                        : "secondary"
                                                                }
                                                                className="position-absolute bottom-0 end-0 p-1 border border-2 border-white"
                                                                pill
                                                            />
                                                        </div>
                                                        <div className="ms-3">
                                                            <h6
                                                                className="mb-0 fw-bold text-truncate"
                                                                style={{
                                                                    maxWidth:
                                                                        "150px",
                                                                }}
                                                            >
                                                                {
                                                                    conversation
                                                                        .other_user
                                                                        .name
                                                                }
                                                                {conversation.is_blocked && (
                                                                    <Badge
                                                                        bg="danger"
                                                                        className="ms-1"
                                                                        pill
                                                                    >
                                                                        Blocked
                                                                    </Badge>
                                                                )}
                                                            </h6>
                                                            <small
                                                                className="text-muted d-block text-truncate"
                                                                style={{
                                                                    maxWidth:
                                                                        "150px",
                                                                }}
                                                            >
                                                                {conversation
                                                                    .last_message
                                                                    ?.sender_id ===
                                                                    auth.user
                                                                        .id &&
                                                                    "You: "}
                                                                {conversation
                                                                    .last_message
                                                                    ?.content ||
                                                                    "No messages yet"}
                                                            </small>
                                                            <small className="text-muted">
                                                                {formatDate(
                                                                    conversation
                                                                        .last_message
                                                                        ?.created_at
                                                                )}
                                                            </small>
                                                        </div>
                                                    </div>
                                                    {conversation.unread_count >
                                                        0 && (
                                                        <Badge bg="danger" pill>
                                                            {
                                                                conversation.unread_count
                                                            }
                                                        </Badge>
                                                    )}
                                                </div>
                                            </ListGroup.Item>
                                        ))}
                                    </ListGroup>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>

                    {/* Chat Area */}
                    <Col md={9} className="h-100">
                        <Card className="h-100 border-0 rounded-0">
                            {selectedConversation ? (
                                <>
                                    <Card.Header className="py-3">
                                        <div className="d-flex justify-content-between align-items-center">
                                            <div className="d-flex align-items-center">
                                                <div className="position-relative me-3">
                                                    <Image
                                                        src={getProfileImage(
                                                            selectedConversation.other_user
                                                        )}
                                                        roundedCircle
                                                        width={45}
                                                        height={45}
                                                        className="object-fit-cover"
                                                        alt={
                                                            selectedConversation
                                                                .other_user.name
                                                        }
                                                    />
                                                    <Badge
                                                        bg={
                                                            selectedConversation
                                                                .other_user
                                                                .online
                                                                ? "success"
                                                                : "secondary"
                                                        }
                                                        className="position-absolute bottom-0 end-0 p-1 border border-2 border-white"
                                                        pill
                                                    />
                                                </div>
                                                <div>
                                                    <h5 className="mb-0 fw-bold">
                                                        {
                                                            selectedConversation
                                                                .other_user.name
                                                        }
                                                        {selectedConversation.is_blocked && (
                                                            <Badge
                                                                bg="danger"
                                                                className="ms-2"
                                                                pill
                                                            >
                                                                Blocked
                                                            </Badge>
                                                        )}
                                                    </h5>
                                                    <small className="text-muted">
                                                        {typingUsers[
                                                            selectedConversation
                                                                .other_user.id
                                                        ] ? (
                                                            <span className="text-primary">
                                                                <Spinner
                                                                    animation="border"
                                                                    size="sm"
                                                                    className="me-1"
                                                                />
                                                                typing...
                                                            </span>
                                                        ) : (
                                                            getLastSeenText(
                                                                selectedConversation.other_user
                                                            )
                                                        )}
                                                    </small>
                                                </div>
                                            </div>
                                            <div className="d-flex align-items-center">
                                                <Badge
                                                    bg={
                                                        isConnected
                                                            ? "success"
                                                            : "warning"
                                                    }
                                                    pill
                                                    className="px-3 me-2"
                                                >
                                                    {isConnected
                                                        ? "Online"
                                                        : "Offline"}
                                                </Badge>
                                                <Dropdown>
                                                    <Dropdown.Toggle
                                                        variant="outline-secondary"
                                                        size="sm"
                                                        className="border-0"
                                                    >
                                                        <MoreVertical
                                                            size={20}
                                                        />
                                                    </Dropdown.Toggle>
                                                    <Dropdown.Menu>
                                                        <Dropdown.Item>
                                                            {selectedConversation.is_muted
                                                                ? "Unmute"
                                                                : "Mute"}
                                                        </Dropdown.Item>
                                                        <Dropdown.Item>
                                                            {selectedConversation.is_archived
                                                                ? "Unarchive"
                                                                : "Archive"}
                                                        </Dropdown.Item>
                                                        <Dropdown.Item className="text-danger">
                                                            {selectedConversation.is_blocked
                                                                ? "Unblock"
                                                                : "Block"}
                                                        </Dropdown.Item>
                                                    </Dropdown.Menu>
                                                </Dropdown>
                                            </div>
                                        </div>
                                    </Card.Header>

                                    <Card.Body className="p-0 overflow-auto bg-light">
                                        <Container className="py-3">
                                            {isLoading ? (
                                                <div className="text-center py-5">
                                                    <Spinner animation="border" />
                                                </div>
                                            ) : messages.length === 0 ? (
                                                <div className="text-center py-5">
                                                    <div className="display-1 mb-3">
                                                        💬
                                                    </div>
                                                    <h5>No messages yet</h5>
                                                    <p className="text-muted">
                                                        Start a conversation
                                                        with{" "}
                                                        {
                                                            selectedConversation
                                                                .other_user.name
                                                        }
                                                        !
                                                    </p>
                                                </div>
                                            ) : (
                                                messages.map((message) => (
                                                    <div
                                                        key={message.id}
                                                        className={`d-flex mb-3 ${
                                                            message.sender_id ===
                                                            auth.user.id
                                                                ? "justify-content-end"
                                                                : "justify-content-start"
                                                        }`}
                                                    >
                                                        <div
                                                            className={`rounded p-3 ${
                                                                message.sender_id ===
                                                                auth.user.id
                                                                    ? "bg-primary text-white"
                                                                    : "bg-white"
                                                            }`}
                                                            style={{
                                                                maxWidth: "70%",
                                                            }}
                                                        >
                                                            <p className="mb-1">
                                                                {
                                                                    message.content
                                                                }
                                                            </p>
                                                            <div
                                                                className={`d-flex justify-content-between align-items-center ${
                                                                    message.sender_id ===
                                                                    auth.user.id
                                                                        ? "text-white-50"
                                                                        : "text-muted"
                                                                }`}
                                                            >
                                                                <small>
                                                                    {formatTime(
                                                                        message.created_at
                                                                    )}
                                                                </small>
                                                                {message.sender_id ===
                                                                    auth.user
                                                                        .id && (
                                                                    <small className="ms-2">
                                                                        {message.is_sending ? (
                                                                            <Spinner
                                                                                animation="border"
                                                                                size="sm"
                                                                            />
                                                                        ) : message.failed ? (
                                                                            <span className="text-danger">
                                                                                ✗
                                                                                Failed
                                                                            </span>
                                                                        ) : message.read_at ? (
                                                                            "✓✓ Read"
                                                                        ) : message.is_delivered ? (
                                                                            "✓✓ Delivered"
                                                                        ) : (
                                                                            "✓ Sent"
                                                                        )}
                                                                    </small>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))
                                            )}
                                            <div ref={messagesEndRef} />
                                        </Container>
                                    </Card.Body>

                                    <Card.Footer className="border-top p-3">
                                        <InputGroup>
                                            <Form.Control
                                                ref={inputRef}
                                                as="textarea"
                                                value={newMessage}
                                                onChange={handleMessageInput}
                                                onKeyPress={handleKeyPress}
                                                onFocus={handleInputFocus}
                                                onBlur={handleInputBlur}
                                                onCompositionStart={
                                                    handleCompositionStart
                                                }
                                                onCompositionEnd={
                                                    handleCompositionEnd
                                                }
                                                placeholder={
                                                    selectedConversation.is_blocked
                                                        ? "Cannot send messages to blocked user"
                                                        : "Type a message..."
                                                }
                                                rows={1}
                                                disabled={
                                                    !isConnected ||
                                                    selectedConversation.is_blocked ||
                                                    isSending
                                                }
                                                className="border-end-0"
                                                style={{
                                                    resize: "none",
                                                    minHeight: "40px",
                                                    maxHeight: "120px",
                                                    overflowY: "auto",
                                                }}
                                            />
                                            <Button
                                                variant="primary"
                                                onClick={sendMessage}
                                                disabled={
                                                    !newMessage.trim() ||
                                                    !isConnected ||
                                                    selectedConversation.is_blocked ||
                                                    isSending
                                                }
                                                className="px-4"
                                            >
                                                {isSending ? (
                                                    <Spinner
                                                        animation="border"
                                                        size="sm"
                                                    />
                                                ) : (
                                                    <Send size={18} />
                                                )}
                                            </Button>
                                        </InputGroup>
                                        {!isConnected && (
                                            <Form.Text className="text-warning">
                                                ⚠️ Not connected to chat
                                                service. Messages may not send.
                                            </Form.Text>
                                        )}
                                        {selectedConversation.is_blocked && (
                                            <Form.Text className="text-danger">
                                                ⚠️ You have blocked this user.
                                                Unblock to send messages.
                                            </Form.Text>
                                        )}
                                    </Card.Footer>
                                </>
                            ) : (
                                <Card.Body className="d-flex align-items-center justify-content-center">
                                    <div className="text-center">
                                        <div className="display-1 mb-4">👋</div>
                                        <h3>Welcome to Messages</h3>
                                        <p className="text-muted">
                                            Select a conversation from the
                                            sidebar to start messaging
                                        </p>
                                    </div>
                                </Card.Body>
                            )}
                        </Card>
                    </Col>
                </Row>
            </Container>
        </AppLayout>
    );
};

export default ChatApp;

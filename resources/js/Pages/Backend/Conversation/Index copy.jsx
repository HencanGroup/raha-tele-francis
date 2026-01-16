// React Core
import React, {
    useState,
    useEffect,
    useRef,
    useCallback,
    useMemo,
    useReducer,
} from "react";

import { v4 as uuid } from "uuid";

// Third-party Libraries
import { Container, Row, Col, Button } from "react-bootstrap";
import { ArrowDown } from "lucide-react";
import { usePage, Head, router } from "@inertiajs/react";

// Utils
import xios from "@/Utils/xios";
import { debounce } from "@/Utils/helpers";
import { messageReducer } from "@/Utils/messageReducer";

// Hooks
import { useErrorToast } from "@/Hooks/useErrorToast";
import { useDivHeights } from "@/Hooks/useSizes";
import {
    useConversationHelpers,
    useFilteredConversations,
} from "@/Hooks/useConversation";

// Components
import NavBar from "@/Components/Pages/NavBar";
import ChatInput from "@/Components/Chat/ChatInput";
import ConversationList from "@/Components/Chat/ConversationList";
import MessageBubble, {
    getMessageStatusIcon,
} from "@/Components/Chat/MessageBubble";
import ChatHeader from "@/Components/Chat/ChatHeader";
import {
    EmptyMessageState,
    EmptyChatState,
} from "@/Components/Chat/EmptyStates";
import ProfileInfo from "@/Components/Chat/ProfileInfo";
import BuyCoinsModal from "@/Components/Modals/BuyCoinsModal";
import AppLayout from "@/Layouts/AppLayout";

const Conversation = () => {
    const {
        conversations = [],
        chatConversation = null,
        messages = [],
        auth,
    } = usePage().props;

    // -------------------------
    // States & Reducer
    // -------------------------
    const [searchTerm, setSearchTerm] = useState("");
    const [isTyping, setIsTyping] = useState(false);
    const [mobileView, setMobileView] = useState(window.innerWidth < 768);
    const [showConversationList, setShowConversationList] = useState(
        !mobileView || !chatConversation
    );
    const [activeFilter, setActiveFilter] = useState("All");
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [showScrollToBottom, setShowScrollToBottom] = useState(false);
    const [showBuyCoinsModal, setShowBuyCoinsModal] = useState(false);
    const [showProfileInfo, setShowProfileInfo] = useState(false);
    const [typingUsers, setTypingUsers] = useState({});

    // Timely reload states
    const [lastRefreshTime, setLastRefreshTime] = useState(Date.now());
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [showRefreshIndicator, setShowRefreshIndicator] = useState(false);

    const [messageState, dispatch] = useReducer(messageReducer, {
        messages: [],
        optimisticMessages: {},
    });

    // -------------------------
    // Refs
    // -------------------------
    const messagesEndRef = useRef();
    const messagesContainerRef = useRef();
    const isUserScrollingRef = useRef(false);
    const shouldScrollToBottomRef = useRef(true);
    const conversationChannelRef = useRef(null);
    const userChannelRef = useRef(null);
    const typingTimeoutRef = useRef(null);

    // Timely reload refs
    const refreshIntervalRef = useRef(null);
    const lastUserActivityRef = useRef(Date.now());

    // -------------------------
    // Hooks
    // -------------------------
    const { showErrorToast } = useErrorToast();
    const navbarHeight = useDivHeights("escort-navbar");
    const { formatTimeMemo, getOtherUser, getMessageStatus } =
        useConversationHelpers(auth);

    // -------------------------
    // Initialize Messages
    // -------------------------
    useEffect(() => {
        if (messages?.length) {
            dispatch({ type: "SET_MESSAGES", payload: messages });
        }
    }, [messages]);

    // -------------------------
    // Timely Reload Functions
    // -------------------------
    const performRefresh = useCallback(
        async (type = "manual") => {
            if (isRefreshing || !navigator.onLine) return;

            setIsRefreshing(true);

            try {
                const only = ["conversations"];

                if (chatConversation) {
                    only.push("messages", "chatConversation");
                }

                await router.reload({
                    only,
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        setLastRefreshTime(Date.now());
                        setShowRefreshIndicator(true);

                        // Hide indicator after delay
                        setTimeout(() => {
                            setShowRefreshIndicator(false);
                        }, 1000);
                    },
                });
            } catch (error) {
                console.error("Refresh failed:", error);
            } finally {
                setIsRefreshing(false);
            }
        },
        [isRefreshing, chatConversation]
    );

    const debouncedPerformRefresh = useCallback(
        debounce(performRefresh, 1000),
        [performRefresh]
    );

    const setupTimelyReload = useCallback(() => {
        if (refreshIntervalRef.current) {
            clearInterval(refreshIntervalRef.current);
        }

        // Only refresh every 2 minutes to prevent flickering
        refreshIntervalRef.current = setInterval(() => {
            const now = Date.now();
            const timeSinceLastRefresh = now - lastRefreshTime;

            if (
                navigator.onLine &&
                timeSinceLastRefresh > 120000 && // 2 minutes
                !isUserScrollingRef.current
            ) {
                debouncedPerformRefresh("interval");
            }
        }, 120000); // Check every 2 minutes

        return () => {
            if (refreshIntervalRef.current) {
                clearInterval(refreshIntervalRef.current);
            }
        };
    }, [lastRefreshTime, debouncedPerformRefresh]);

    // -------------------------
    // Memoized Values
    // -------------------------
    const activeOtherUser = useMemo(
        () => getOtherUser(chatConversation),
        [chatConversation, getOtherUser]
    );

    const allMessages = useMemo(() => {
        const base = Array.isArray(messageState.messages)
            ? messageState.messages
            : [];

        const optimistic =
            messageState.optimisticMessages?.[chatConversation?.id] || [];

        const map = new Map();

        // Add real messages first
        base.forEach((msg) => {
            map.set(msg.id, msg);
        });

        // Add optimistic messages (will replace if real message exists)
        optimistic.forEach((msg) => {
            const key = msg.id || msg.client_id;
            map.set(key, msg);
        });

        return Array.from(map.values()).sort(
            (a, b) => new Date(a.created_at) - new Date(b.created_at)
        );
    }, [
        messageState.messages,
        messageState.optimisticMessages,
        chatConversation?.id,
    ]);

    const filteredConversations = useFilteredConversations(
        conversations,
        searchTerm,
        activeFilter,
        getOtherUser
    );

    // -------------------------
    // Handlers
    // -------------------------
    const scrollToBottom = useCallback((behavior = "smooth") => {
        if (messagesEndRef.current && shouldScrollToBottomRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior });
            setShowScrollToBottom(false);
        }
    }, []);

    const handleScroll = useCallback(() => {
        if (!messagesContainerRef.current) return;

        const container = messagesContainerRef.current;
        const isAtBottom =
            container.scrollHeight -
                container.scrollTop -
                container.clientHeight <
            50;

        shouldScrollToBottomRef.current = isAtBottom;
        isUserScrollingRef.current = !isAtBottom;
        setShowScrollToBottom(!isAtBottom);
        lastUserActivityRef.current = Date.now();
    }, []);

    const handleChatSelect = useCallback(
        (conversation) => {
            const otherUser = getOtherUser(conversation);
            if (!otherUser) return;

            shouldScrollToBottomRef.current = true;
            isUserScrollingRef.current = false;

            dispatch({ type: "SET_MESSAGES", payload: [] });

            router.get(
                route("conversation.show", otherUser.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        if (mobileView) setShowConversationList(false);
                        setTimeout(() => scrollToBottom("auto"), 200);
                    },
                }
            );
        },
        [getOtherUser, mobileView, scrollToBottom]
    );

    // -------------------------
    // Typing Indicator
    // -------------------------
    const handleTyping = useCallback(
        debounce((isTyping) => {
            if (!chatConversation?.id || !window.Echo) return;

            const channel = window.Echo.private(
                `chat.conversation.${chatConversation.id}`
            );
            channel.whisper("typing", {
                userId: auth.user.id,
                isTyping,
            });
        }, 500),
        [chatConversation?.id, auth.user.id]
    );

    // -------------------------
    // Send Message - FIXED VERSION
    // -------------------------
    const handleSendMessage = async (messageData) => {
        const { message, attachment, type, requires_credit, credit_cost } =
            messageData;

        if ((!message?.trim() && !attachment) || !chatConversation) return;

        const client_id = uuid();

        // 1. Add optimistic message
        const optimisticMessage = {
            client_id,
            sender_id: auth.user.id,
            conversation_id: chatConversation.id,
            message: message || null,
            attachment_name: attachment?.name || null,
            type: type || "text",
            created_at: new Date().toISOString(),
            is_optimistic: true,
            status: "sending",
            sender: {
                id: auth.user.id,
                name: auth.user.name,
                avatar: auth.user.avatar,
            },
        };

        dispatch({
            type: "ADD_OPTIMISTIC",
            payload: {
                conversationId: chatConversation.id,
                message: optimisticMessage,
            },
        });

        shouldScrollToBottomRef.current = true;
        requestAnimationFrame(() => scrollToBottom("smooth"));

        try {
            const formData = new FormData();
            formData.append("conversation_id", chatConversation.id);
            formData.append("message", message || "");
            formData.append("type", type || "text");
            formData.append("client_id", client_id);
            if (requires_credit) formData.append("requires_credit", 1);
            if (credit_cost) formData.append("credit_cost", credit_cost);
            if (attachment) formData.append("attachment", attachment);

            const { data } = await xios.post(route("message.store"), formData);

            // 2. Update optimistic message with real ID immediately
            dispatch({
                type: "UPDATE_OPTIMISTIC_TO_REAL",
                payload: {
                    conversationId: chatConversation.id,
                    client_id,
                    realMessage: data.message,
                },
            });

            // 3. Refresh conversations list without reloading messages
            router.reload({
                only: ["conversations"],
                preserveScroll: true,
                preserveState: true,
            });

            // Clear typing indicator
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
            }
            handleTyping(false);
        } catch (error) {
            dispatch({
                type: "UPDATE_OPTIMISTIC_STATUS",
                payload: {
                    conversationId: chatConversation.id,
                    client_id,
                    status: "failed",
                },
            });

            showErrorToast(error);
        }
    };

    // -------------------------
    // Echo Listeners - FIXED VERSION
    // -------------------------
    const setupEchoListeners = useCallback(() => {
        if (!window.Echo) return;

        // Clean up previous listeners
        if (conversationChannelRef.current) {
            window.Echo.leave(`chat.conversation.${chatConversation?.id}`);
            conversationChannelRef.current = null;
        }

        if (userChannelRef.current) {
            window.Echo.leave(`user.${auth.user.id}`);
            userChannelRef.current = null;
        }

        // Listen for user-specific events (delivered/read receipts)
        const userChannel = window.Echo.private(`user.${auth.user.id}`);
        userChannelRef.current = userChannel;

        userChannel.listen(".message.delivered", (e) => {
            dispatch({
                type: "UPDATE_MESSAGE_STATUS",
                payload: {
                    messageId: e.message_id,
                    status: "delivered",
                    deliveredAt: e.delivered_at,
                },
            });
        });

        userChannel.listen(".message.read", (e) => {
            dispatch({
                type: "UPDATE_MESSAGE_STATUS",
                payload: {
                    messageId: e.message_id,
                    status: "read",
                    readAt: e.read_at,
                },
            });

            // Refresh conversation list to update unread counts
            router.reload({
                only: ["conversations"],
                preserveScroll: true,
                preserveState: true,
            });
        });

        // Listen for conversation events if in a chat
        if (chatConversation?.id) {
            const conversationChannel = window.Echo.private(
                `chat.conversation.${chatConversation.id}`
            );
            conversationChannelRef.current = conversationChannel;

            // Listen for new messages (from other users)
            conversationChannel.listen(".message.sent", (e) => {
                console.log("Echo message received:", e.message);
                console.log("Current messages:", allMessages);

                // Only add if message doesn't already exist
                const existingMessage = allMessages.find(
                    (msg) =>
                        msg.id === e.message.id ||
                        msg.client_id === e.message.client_id
                );

                if (!existingMessage) {
                    dispatch({
                        type: "ADD_MESSAGE",
                        payload: e.message,
                    });

                    if (shouldScrollToBottomRef.current) {
                        requestAnimationFrame(() => scrollToBottom("smooth"));
                    }

                    // Refresh conversation list
                    router.reload({
                        only: ["conversations"],
                        preserveScroll: true,
                        preserveState: true,
                    });
                }
            });

            // Listen for typing indicators
            conversationChannel.listenForWhisper("typing", (e) => {
                setTypingUsers((prev) => ({
                    ...prev,
                    [e.userId]: e.isTyping,
                }));
            });
        }

        return () => {
            if (conversationChannelRef.current) {
                window.Echo.leave(conversationChannelRef.current);
            }
            if (userChannelRef.current) {
                window.Echo.leave(userChannelRef.current);
            }
        };
    }, [chatConversation?.id, auth.user.id, allMessages, scrollToBottom]);

    // -------------------------
    // Effects
    // -------------------------
    useEffect(() => {
        setupEchoListeners();
        setupTimelyReload();

        return () => {
            if (refreshIntervalRef.current) {
                clearInterval(refreshIntervalRef.current);
            }
            if (conversationChannelRef.current) {
                window.Echo.leave(conversationChannelRef.current);
            }
            if (userChannelRef.current) {
                window.Echo.leave(userChannelRef.current);
            }
        };
    }, [setupEchoListeners, setupTimelyReload]);

    useEffect(() => {
        const handleResize = debounce(() => {
            const isMobile = window.innerWidth < 768;
            setMobileView(isMobile);
            if (!isMobile) setShowConversationList(true);
            else if (!chatConversation) setShowConversationList(true);
        }, 250);

        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, [chatConversation]);

    useEffect(() => {
        const container = messagesContainerRef.current;
        if (container) container.addEventListener("scroll", handleScroll);

        const handleVisibilityChange = () => {
            if (!document.hidden) {
                setupEchoListeners();
                if (Date.now() - lastRefreshTime > 30000) {
                    debouncedPerformRefresh("visibility_change");
                }
            }
        };

        const handleOnline = () => {
            setIsOnline(true);
            setupEchoListeners();
            debouncedPerformRefresh("network_online");
        };

        const handleOffline = () => {
            setIsOnline(false);
        };

        window.addEventListener("online", handleOnline);
        window.addEventListener("offline", handleOffline);
        document.addEventListener("visibilitychange", handleVisibilityChange);

        return () => {
            if (container)
                container.removeEventListener("scroll", handleScroll);
            window.removeEventListener("online", handleOnline);
            window.removeEventListener("offline", handleOffline);
            document.removeEventListener(
                "visibilitychange",
                handleVisibilityChange
            );
        };
    }, [
        handleScroll,
        setupEchoListeners,
        debouncedPerformRefresh,
        lastRefreshTime,
    ]);

    useEffect(() => {
        if (chatConversation && shouldScrollToBottomRef.current) {
            setTimeout(() => scrollToBottom("auto"), 100);
        }
    }, [chatConversation?.id, scrollToBottom]);

    // -------------------------
    // Typing Effect
    // -------------------------
    useEffect(() => {
        const otherUserTyping = typingUsers[activeOtherUser?.id];
        setIsTyping(!!otherUserTyping);

        // Auto-clear typing after 3 seconds
        if (otherUserTyping) {
            const timeout = setTimeout(() => {
                setTypingUsers((prev) => ({
                    ...prev,
                    [activeOtherUser?.id]: false,
                }));
            }, 3000);

            return () => clearTimeout(timeout);
        }
    }, [typingUsers, activeOtherUser?.id]);

    // -------------------------
    // Manual Refresh
    // -------------------------
    const handleManualRefresh = () => {
        performRefresh("manual");
    };

    // -------------------------
    // Render
    // -------------------------
    const isMobile = mobileView;
    const showChatColumn = !isMobile || (isMobile && !showConversationList);

    return (
        <AppLayout
            showHeaderLinks={false}
            showNavBar={true}
            showFooter={false}
            navBarFluid={true}
        >
            <Head title="Messages" />
            <Container
                fluid
                className="bg-dark p-0"
                style={{ height: `calc(100vh - ${navbarHeight}px)` }}
            >
                <Row className="h-100 m-0">
                    {(showConversationList || !isMobile) && (
                        <Col
                            md={3}
                            className={`h-100 d-flex flex-column p-0 ${
                                isMobile && showConversationList
                                    ? ""
                                    : "d-none d-md-flex"
                            }`}
                        >
                            <ConversationList
                                searchTerm={searchTerm}
                                setSearchTerm={setSearchTerm}
                                activeFilter={activeFilter}
                                setActiveFilter={setActiveFilter}
                                filteredConversations={filteredConversations}
                                chatConversation={chatConversation}
                                auth={auth}
                                getOtherUser={getOtherUser}
                                formatTimeMemo={formatTimeMemo}
                                getMessageStatus={getMessageStatus}
                                getMessageStatusIcon={getMessageStatusIcon}
                                handleChatSelect={handleChatSelect}
                                isRefreshing={isRefreshing}
                                showRefreshIndicator={showRefreshIndicator}
                            />
                        </Col>
                    )}
                    {showChatColumn && (
                        <Col
                            md={showProfileInfo ? 6 : 9}
                            className="h-100 d-flex flex-column p-0"
                            style={{ backgroundColor: "#0c0c0c" }}
                        >
                            {chatConversation ? (
                                <>
                                    <ChatHeader
                                        mobileView={isMobile}
                                        activeOtherUser={activeOtherUser}
                                        formatTime={formatTimeMemo}
                                        onCloseChat={() =>
                                            router.get(
                                                route("conversation.index")
                                            )
                                        }
                                        showProfileInfo={showProfileInfo}
                                        setShowProfileInfo={setShowProfileInfo}
                                        onManualRefresh={handleManualRefresh}
                                        isRefreshing={isRefreshing}
                                        showRefreshIndicator={
                                            showRefreshIndicator
                                        }
                                    />
                                    <div
                                        ref={messagesContainerRef}
                                        className="flex-grow-1 p-3 overflow-auto position-relative"
                                        onScroll={handleScroll}
                                    >
                                        {allMessages.length > 0 ? (
                                            <>
                                                {allMessages.map((message) => (
                                                    <MessageBubble
                                                        key={
                                                            message.id ||
                                                            message.client_id ||
                                                            `temp_${message.tempId}`
                                                        }
                                                        message={message}
                                                        auth={auth}
                                                        formatTime={
                                                            formatTimeMemo
                                                        }
                                                        getMessageStatus={
                                                            getMessageStatus
                                                        }
                                                        getMessageStatusIcon={
                                                            getMessageStatusIcon
                                                        }
                                                    />
                                                ))}
                                                <div ref={messagesEndRef} />
                                            </>
                                        ) : (
                                            <EmptyMessageState
                                                otherUserName={
                                                    activeOtherUser?.name
                                                }
                                            />
                                        )}

                                        {showScrollToBottom && (
                                            <Button
                                                variant="warning"
                                                className="position-absolute bottom-0 end-0 m-3 rounded-circle p-2"
                                                onClick={() =>
                                                    scrollToBottom("smooth")
                                                }
                                            >
                                                <ArrowDown size={20} />
                                            </Button>
                                        )}

                                        {isTyping && (
                                            <div className="text-muted small ms-3 mb-2">
                                                {activeOtherUser?.name} is
                                                typing...
                                            </div>
                                        )}
                                    </div>

                                    <ChatInput
                                        handleSendMessage={handleSendMessage}
                                        onTypingChange={handleTyping}
                                        userCredits={auth?.user?.credits}
                                        onBuyCoins={() =>
                                            setShowBuyCoinsModal(true)
                                        }
                                        isOnline={isOnline}
                                        conversationId={chatConversation?.id}
                                        auth={auth}
                                    />
                                </>
                            ) : (
                                !isMobile && <EmptyChatState />
                            )}
                        </Col>
                    )}

                    {showProfileInfo && (
                        <Col md={3} className="h-100 d-flex flex-column p-0">
                            <ProfileInfo
                                otherUser={activeOtherUser}
                                formatTime={formatTimeMemo}
                                auth={auth}
                                getMessageStatus={getMessageStatus}
                                getMessageStatusIcon={getMessageStatusIcon}
                                onClose={() => setShowProfileInfo(false)}
                            />
                        </Col>
                    )}
                </Row>
            </Container>

            <BuyCoinsModal
                showBuyCoinsModal={showBuyCoinsModal}
                setShowBuyCoinsModal={setShowBuyCoinsModal}
            />
        </AppLayout>
    );
};

export default Conversation;

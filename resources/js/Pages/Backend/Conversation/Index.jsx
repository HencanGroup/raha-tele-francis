// React & Core
import React, {
    useState,
    useEffect,
    useRef,
    useCallback,
    useMemo,
    useReducer,
} from "react";

// Third-party libraries
import { Container, Row, Col, Alert, Button } from "react-bootstrap";
import { ArrowDown, WifiOff } from "lucide-react";
import { usePage, Head, router } from "@inertiajs/react";
import { ToastContainer } from "react-toastify";

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

const Conversation = () => {
    const {
        conversations = [],
        chatConversation = null,
        messages = [],
        auth,
    } = usePage().props;

    // State
    const [searchTerm, setSearchTerm] = useState("");
    const [isTyping, setIsTyping] = useState(false);
    const [mobileView, setMobileView] = useState(window.innerWidth < 768);
    const [showConversationList, setShowConversationList] = useState(
        !mobileView || !chatConversation
    );
    const [activeFilter, setActiveFilter] = useState("All");
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [showScrollToBottom, setShowScrollToBottom] = useState(false);
    const [newMessage, setNewMessage] = useState("");
    const [showBuyCoinsModal, setShowBuyCoinsModal] = useState(false);
    const [showProfileInfo, setShowProfileInfo] = useState(false);
    const [lastMessageUpdate, setLastMessageUpdate] = useState(Date.now());

    // Reducer for messages
    const [messageState, dispatch] = useReducer(messageReducer, {
        messages: [],
        optimisticMessages: {},
    });

    // Refs
    const messagesEndRef = useRef();
    const messagesContainerRef = useRef();
    const isUserScrollingRef = useRef(false);
    const shouldScrollToBottomRef = useRef(true);
    const echoChannelRef = useRef(null);
    const reloadTimeoutRef = useRef(null);

    // Hooks
    const { showErrorToast } = useErrorToast();
    const navbarHeight = useDivHeights("escort-navbar");
    const { formatTimeMemo, getOtherUser, getMessageStatus } =
        useConversationHelpers(auth);

    // Combined messages
    const allMessages = useMemo(() => {
        const base = messageState.messages.length
            ? messageState.messages
            : messages;
        const optimistic =
            chatConversation?.id &&
            messageState.optimisticMessages[chatConversation.id]
                ? messageState.optimisticMessages[chatConversation.id]
                : [];
        return [...base, ...optimistic].sort(
            (a, b) => new Date(a.created_at) - new Date(b.created_at)
        );
    }, [
        messageState.messages,
        messageState.optimisticMessages,
        messages,
        chatConversation?.id,
    ]);

    // Active other user
    const activeOtherUser = useMemo(
        () => getOtherUser(chatConversation),
        [chatConversation, getOtherUser]
    );

    // Filtered conversations
    const filteredConversations = useFilteredConversations(
        conversations,
        searchTerm,
        activeFilter,
        getOtherUser
    );

    // Scroll handlers
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
    }, []);

    const scrollToBottom = useCallback((behavior = "smooth") => {
        if (messagesEndRef.current && shouldScrollToBottomRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior });
            setShowScrollToBottom(false);
        }
    }, []);

    // Mark messages as read
    const markMessagesAsRead = async (messageIds) => {
        try {
            await xios.post(route("messages.mark-read"), {
                conversation_id: chatConversation.id,
                message_ids: messageIds,
            });
            dispatch({ type: "MARK_AS_READ", payload: { messageIds } });
        } catch (error) {
            console.error("Failed to mark messages as read:", error);
        }
    };

    // Optimized reload with debouncing
    const reloadData = useCallback(
        async (force = false) => {
            if (!force && document.hidden) return;

            if (reloadTimeoutRef.current) {
                clearTimeout(reloadTimeoutRef.current);
            }

            reloadTimeoutRef.current = setTimeout(async () => {
                try {
                    await router.reload({
                        only: ["messages", "conversations", "chatConversation"],
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () => {
                            setLastMessageUpdate(Date.now());
                            if (shouldScrollToBottomRef.current) {
                                setTimeout(() => scrollToBottom("auto"), 100);
                            }
                        },
                    });
                } catch (error) {
                    console.error("Reload failed:", error);
                }
            }, 100);
        },
        [scrollToBottom]
    );

    // Handle chat selection
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

    // Handle send message
    const handleSendMessage = async ({
        message,
        attachment,
        type,
        requires_credit,
        credit_cost,
    }) => {
        if ((!message?.trim() && !attachment) || !chatConversation) return;

        const tempId = `temp_${Date.now()}`;

        const optimisticMessage = {
            tempId,
            id: tempId,
            sender_id: auth.user.id,
            message: message || null,
            attachment_name: attachment?.name || null,
            type: type || "text",
            created_at: new Date().toISOString(),
            is_optimistic: true,
            status: "sending",
        };

        // 1️⃣ Add optimistic message immediately
        dispatch({
            type: "ADD_OPTIMISTIC",
            payload: {
                conversationId: chatConversation.id,
                message: optimisticMessage,
            },
        });

        // Immediately scroll to show new message
        shouldScrollToBottomRef.current = true;
        requestAnimationFrame(() => scrollToBottom("smooth"));

        try {
            // 2️⃣ Prepare payload
            const formData = new FormData();
            formData.append("conversation_id", chatConversation.id);
            formData.append("message", message || "");
            formData.append("type", type || "text");
            formData.append("requires_credit", requires_credit ? 1 : 0);
            formData.append("credit_cost", credit_cost || 0);
            if (attachment) {
                formData.append("attachment", attachment);
            }

            // 3️⃣ Send message
            await xios.post(route("message.store"), formData);

            // 4️⃣ Remove optimistic message (will be replaced by real message from Echo)
            dispatch({
                type: "REMOVE_OPTIMISTIC",
                payload: {
                    conversationId: chatConversation.id,
                    tempId,
                },
            });

            // 5️⃣ Clear input and trigger immediate refresh
            setNewMessage("");
            reloadData(true);
        } catch (error) {
            // 6️⃣ Rollback optimistic UI on failure
            dispatch({
                type: "REMOVE_OPTIMISTIC",
                payload: {
                    conversationId: chatConversation.id,
                    tempId,
                },
            });

            // Update status to failed
            dispatch({
                type: "UPDATE_OPTIMISTIC_STATUS",
                payload: {
                    conversationId: chatConversation.id,
                    tempId,
                    status: "failed",
                },
            });

            showErrorToast(error);
        }
    };

    // Setup Echo/WebSocket listeners
    const setupEchoListeners = useCallback(() => {
        if (!chatConversation?.id || !window.echo) return;

        // Clean up previous channel
        if (echoChannelRef.current) {
            window.echo.leaveChannel(echoChannelRef.current);
            echoChannelRef.current = null;
        }

        const channelName = `chat.${chatConversation.id}`;
        const channel = window.echo.private(channelName);
        echoChannelRef.current = channelName;

        channel.listen("MessageSent", (e) => {
            console.log("Message received via Echo:", e.message);

            // Add message to state
            dispatch({ type: "ADD_MESSAGES", payload: [e.message] });

            // Mark as read if from other user
            if (e.message.sender_id !== auth.user.id) {
                markMessagesAsRead([e.message.id]);
            }

            // Force refresh to sync everything
            reloadData();

            // Scroll to bottom if needed
            if (shouldScrollToBottomRef.current) {
                setTimeout(() => scrollToBottom("smooth"), 100);
            }
        });

        channel.listenForWhisper("typing", (e) => {
            setIsTyping(e.isTyping);
        });

        // Listen for conversation updates
        channel.listen("ConversationUpdated", (e) => {
            console.log("Conversation updated via Echo:", e);
            reloadData();
        });

        return () => {
            if (echoChannelRef.current) {
                window.echo.leaveChannel(echoChannelRef.current);
                echoChannelRef.current = null;
            }
        };
    }, [chatConversation?.id, auth.user.id, reloadData, scrollToBottom]);

    // Effects
    useEffect(() => {
        // Initialize messages
        if (messages.length) {
            dispatch({ type: "SET_MESSAGES", payload: messages });
        }

        // Setup Echo listeners
        setupEchoListeners();

        return () => {
            if (echoChannelRef.current && window.echo) {
                window.echo.leaveChannel(echoChannelRef.current);
                echoChannelRef.current = null;
            }
        };
    }, [chatConversation?.id, messages, setupEchoListeners]);

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
                reloadData(true);
                // Re-setup Echo when tab becomes visible
                setupEchoListeners();
            }
        };
        document.addEventListener("visibilitychange", handleVisibilityChange);

        const handleOnline = () => {
            setIsOnline(true);
            reloadData(true);
            setupEchoListeners();
        };
        const handleOffline = () => setIsOnline(false);

        window.addEventListener("online", handleOnline);
        window.addEventListener("offline", handleOffline);

        // Periodic refresh as backup (every 30 seconds)
        const refreshInterval = setInterval(() => {
            if (document.visibilityState === "visible" && isOnline) {
                reloadData();
            }
        }, 30000);

        return () => {
            if (container) {
                container.removeEventListener("scroll", handleScroll);
            }
            document.removeEventListener(
                "visibilitychange",
                handleVisibilityChange
            );
            window.removeEventListener("online", handleOnline);
            window.removeEventListener("offline", handleOffline);
            clearInterval(refreshInterval);
            if (reloadTimeoutRef.current) {
                clearTimeout(reloadTimeoutRef.current);
            }
        };
    }, [handleScroll, reloadData, isOnline, setupEchoListeners]);

    useEffect(() => {
        shouldScrollToBottomRef.current = true;
        isUserScrollingRef.current = false;
        if (chatConversation) {
            setTimeout(() => scrollToBottom("auto"), 100);
        }
    }, [chatConversation?.id, scrollToBottom, lastMessageUpdate]);

    // Polling as fallback (only if no WebSocket)
    useEffect(() => {
        let pollingInterval;

        if (chatConversation?.id && !window.echo && isOnline) {
            pollingInterval = setInterval(() => {
                reloadData();
            }, 5000); // Poll every 5 seconds if no WebSocket
        }

        return () => {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        };
    }, [chatConversation?.id, isOnline, reloadData]);

    // Render
    return (
        <>
            <Head title="Messages" />
            {!isOnline && (
                <Alert variant="warning" className="m-0 py-1 text-center">
                    <WifiOff size={16} className="me-2" />
                    You are offline. Reconnecting...
                </Alert>
            )}
            <ToastContainer position="top-center" autoClose={2000} />
            <NavBar fluid />

            <Container
                fluid
                className="bg-dark p-0"
                style={{ height: `calc(100vh - ${navbarHeight}px)` }}
            >
                <Row className="h-100 m-0">
                    {(showConversationList || !mobileView) && (
                        <Col
                            md={3}
                            className={`h-100 d-flex flex-column p-0 ${
                                mobileView && showConversationList
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
                            />
                        </Col>
                    )}

                    <Col
                        md={showProfileInfo ? 6 : 9}
                        className={`h-100 d-flex flex-column p-0 ${
                            mobileView && showConversationList
                                ? "d-none"
                                : "d-flex"
                        }`}
                        style={{ backgroundColor: "#0c0c0c" }}
                    >
                        {chatConversation ? (
                            <>
                                <ChatHeader
                                    mobileView={mobileView}
                                    activeOtherUser={activeOtherUser}
                                    formatTime={formatTimeMemo}
                                    onCloseChat={() =>
                                        router.get(route("conversation.index"))
                                    }
                                    showProfileInfo={showProfileInfo}
                                    setShowProfileInfo={setShowProfileInfo}
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
                                                        message.tempId
                                                    }
                                                    message={message}
                                                    auth={auth}
                                                    formatTime={formatTimeMemo}
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
                                                getOtherUser(chatConversation)
                                                    ?.name
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
                                            {activeOtherUser?.name} is typing...
                                        </div>
                                    )}
                                </div>

                                <ChatInput
                                    newMessage={newMessage}
                                    setNewMessage={setNewMessage}
                                    handleSendMessage={handleSendMessage}
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
                            !mobileView && <EmptyChatState />
                        )}
                    </Col>

                    {showProfileInfo && (
                        <Col md={3} className={`h-100 d-flex flex-column p-0`}>
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
        </>
    );
};

export default Conversation;

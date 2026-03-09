import React, { useEffect, useRef, useCallback, useMemo } from "react";
import { usePage } from "@inertiajs/react";
import { Image, Badge } from "react-bootstrap";
import dayjs from "dayjs";
import { InView } from "react-intersection-observer";
import { getProfileImage } from "@/Utils/helpers";

export default function MessageList({
    messages,
    conversation,
    onMarkAsRead,
    messagesEndRef,
}) {
    const { auth } = usePage().props;
    const messageRefs = useRef({});

    useEffect(() => {
        // Mark messages as read when they come into view
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const messageId = entry.target.dataset.messageId;
                        const message = messages.find(
                            (m) => m.id === messageId,
                        );

                        if (
                            message &&
                            !message.is_read &&
                            message.receiver_id === auth.user.id
                        ) {
                            onMarkAsRead();
                        }
                    }
                });
            },
            { threshold: 0.5 },
        );

        // Observe all message elements
        Object.values(messageRefs.current).forEach((ref) => {
            if (ref) observer.observe(ref);
        });

        return () => observer.disconnect();
    }, [messages, auth.user.id, onMarkAsRead]);

    const groupMessagesByDate = useCallback(() => {
        const groups = {};
        messages.forEach((message) => {
            const date = dayjs(message.created_at).format("YYYY-MM-DD");
            if (!groups[date]) {
                groups[date] = [];
            }
            groups[date].push(message);
        });
        return groups;
    }, [messages]);

    const messageGroups = useMemo(
        () => groupMessagesByDate(),
        [groupMessagesByDate],
    );

    const getReadReceiptIcon = useCallback((message) => {
        if (message.is_read) {
            return <i className="bi bi-check2-all text-success"></i>;
        }
        if (message.is_delivered) {
            return <i className="bi bi-check2 text-white-50"></i>;
        }
        return <i className="bi bi-clock text-white-50"></i>;
    }, []);

    const renderMessage = useCallback(
        (message, index, dateMessages) => {
            const isMine = message.sender.id === auth.user.id;
            const showAvatar =
                !isMine &&
                (index === 0 ||
                    dateMessages[index - 1]?.sender.id !== message.sender.id);

            return (
                <div
                    key={message.id}
                    ref={(el) => (messageRefs.current[message.id] = el)}
                    data-message-id={message.id}
                    className={`d-flex ${isMine ? "justify-content-end" : "justify-content-start"} mb-3`}
                >
                    <div
                        className={`d-flex gap-2 ${isMine ? "flex-row-reverse" : "flex-row"}`}
                        style={{ maxWidth: "70%" }}
                    >
                        {/* Avatar for other users */}
                        {!isMine && showAvatar && (
                            <div className="flex-shrink-0 align-self-end mb-1">
                                <Image
                                    src={getProfileImage(message.sender)}
                                    alt={message.sender.name}
                                    roundedCircle
                                    width={28}
                                    height={28}
                                    className="object-fit-cover"
                                />
                            </div>
                        )}

                        {/* Avatar placeholder for alignment when no avatar */}
                        {!isMine && !showAvatar && (
                            <div
                                className="flex-shrink-0"
                                style={{ width: 28 }}
                            ></div>
                        )}

                        {/* Message Bubble */}
                        <div className="flex-grow-1">
                            {/* Sender name for group chats (optional) */}
                            {!isMine && showAvatar && conversation.is_group && (
                                <small className="text-white-50 d-block mb-1">
                                    {message.sender.name}
                                </small>
                            )}

                            <div
                                className={`px-3 py-2 rounded-3 ${
                                    isMine
                                        ? "bg-warning text-dark"
                                        : "bg-light bg-opacity-25 text-white"
                                }`}
                                style={{
                                    wordBreak: "break-word",
                                    boxShadow: "0 1px 2px rgba(0,0,0,0.1)",
                                }}
                            >
                                <p className="mb-0 small">{message.message}</p>
                            </div>

                            {/* Timestamp */}
                            <div
                                className={`d-flex align-items-center mt-1 small ${
                                    isMine
                                        ? "justify-content-end"
                                        : "justify-content-start"
                                }`}
                            >
                                <span className="text-white-50">
                                    {dayjs(message.created_at).format("h:mm A")}
                                </span>
                                {isMine && (
                                    <span className="ms-2 d-flex align-items-center">
                                        {getReadReceiptIcon(message)}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            );
        },
        [auth.user.id, conversation.is_group, getReadReceiptIcon],
    );

    const formatDateHeading = useCallback((date) => {
        const today = dayjs().format("YYYY-MM-DD");
        const yesterday = dayjs().subtract(1, "day").format("YYYY-MM-DD");

        if (date === today) {
            return "Today";
        } else if (date === yesterday) {
            return "Yesterday";
        } else {
            return dayjs(date).format("MMMM D, YYYY");
        }
    }, []);

    return (
        <div className="flex-grow-1 overflow-auto p-3 bg-dark">
            {Object.entries(messageGroups).map(([date, dateMessages]) => (
                <div key={date}>
                    {/* Date Separator */}
                    <div className="d-flex justify-content-center my-4">
                        <Badge
                            bg="secondary"
                            className="px-3 py-2 rounded-pill text-white-50 bg-opacity-25"
                            style={{ fontSize: "0.75rem" }}
                        >
                            <i className="bi bi-calendar3 me-2"></i>
                            {formatDateHeading(date)}
                        </Badge>
                    </div>

                    {/* Messages */}
                    {dateMessages.map((message, index) =>
                        renderMessage(message, index, dateMessages),
                    )}
                </div>
            ))}

            {/* Loading indicator for older messages (optional) */}
            {messages.length > 0 && (
                <div className="text-center my-2">
                    <small className="text-white-50">
                        <i className="bi bi-arrow-up me-1"></i>
                        Scroll for older messages
                    </small>
                </div>
            )}

            {/* Scroll anchor */}
            <div ref={messagesEndRef} />
        </div>
    );
}

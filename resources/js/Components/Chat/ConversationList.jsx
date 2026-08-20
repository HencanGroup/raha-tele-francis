import React, { useState, useMemo, useCallback } from "react";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import { Link, router, usePage } from "@inertiajs/react";
import {
    Form,
    Image,
    Badge,
    ListGroup,
    Button,
    Modal,
    Spinner,
} from "react-bootstrap";
import { getProfileImage } from "@/Utils/helpers";
import axios from "axios";

dayjs.extend(relativeTime);

export default function ConversationList({
    conversations = [],
    archivedCount = 0,
    className = "",
}) {
    const { auth } = usePage().props;
    // Chat is strictly escort ↔ member, so the New Chat modal only ever lists
    // the opposite actor type: members see escorts, escorts see members.
    const targetLabel =
        auth?.user?.user_type === "member"
            ? "escorts"
            : auth?.user?.user_type === "escort"
              ? "members"
              : "users";
    const [search, setSearch] = useState("");
    const [filterType, setFilterType] = useState("all");
    const [showNewChatModal, setShowNewChatModal] = useState(false);
    const [users, setUsers] = useState([]);
    const [userSearch, setUserSearch] = useState("");
    const [loading, setLoading] = useState(false);
    const [creatingUserId, setCreatingUserId] = useState(null);

    // Filter conversations
    const filteredConversations = useMemo(() => {
        if (!conversations.length) return [];

        let filtered = conversations.filter((conv) =>
            conv.other_user.name.toLowerCase().includes(search.toLowerCase()),
        );

        if (filterType === "unread") {
            filtered = filtered.filter((conv) => conv.unread_count > 0);
        } else if (filterType === "favorites") {
            filtered = filtered.filter((conv) => conv.is_favorite);
        }

        return filtered;
    }, [conversations, search, filterType]);

    // Filter users for new chat
    const filteredUsers = useMemo(() => {
        if (!users.length) return [];

        return users.filter(
            (user) =>
                user.name.toLowerCase().includes(userSearch.toLowerCase()) ||
                (user.email &&
                    user.email
                        .toLowerCase()
                        .includes(userSearch.toLowerCase())),
        );
    }, [users, userSearch]);

    // Load users for new chat
    const loadUsers = useCallback(async () => {
        setLoading(true);
        try {
            const response = await axios.get("/chat/users");
            setUsers(response.data);
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    }, []);

    const handleOpenNewChat = useCallback(() => {
        setShowNewChatModal(true);
        loadUsers();
        setUserSearch("");
    }, [loadUsers]);

    const handleStartConversation = useCallback((userId) => {
        setCreatingUserId(userId);
        router.post(
            "/chat/start",
            { user_id: userId },
            {
                onSuccess: () => {
                    setShowNewChatModal(false);
                    setCreatingUserId(null);
                },
                onError: () => {
                    setCreatingUserId(null);
                },
            },
        );
    }, []);

    const handleSearchChange = useCallback((e) => {
        setSearch(e.target.value);
    }, []);

    const handleFilterChange = useCallback((type) => {
        setFilterType(type);
    }, []);

    const formatMessagePreview = useCallback((conversation) => {
        if (!conversation.last_message) {
            return "No messages yet";
        }

        const { type, message } = conversation.last_message;

        if (type === "text") {
            return message?.length > 50
                ? `${message.substring(0, 50)}...`
                : message;
        }

        const mediaEmoji =
            {
                image: "📷",
                video: "🎥",
                audio: "🎤",
                file: "📎",
                document: "📄",
            }[type] || "📎";

        return `${mediaEmoji} Sent a ${type}`;
    }, []);

    const getTimeAgo = useCallback((timestamp) => {
        if (!timestamp) return "";

        const now = dayjs();
        const date = dayjs(timestamp);
        const diffInDays = now.diff(date, "day");

        if (diffInDays === 0) {
            return date.format("h:mm a");
        } else if (diffInDays === 1) {
            return "Yesterday";
        } else if (diffInDays < 7) {
            return date.format("dddd");
        }

        return date.format("MMM D");
    }, []);

    const getFilterButtonVariant = (type) => {
        if (filterType === type) {
            return type === "favorites" ? "warning" : "gold";
        }
        return "outline-secondary";
    };

    return (
        <>
            <div className={`d-flex flex-column h-100 bg-dark ${className}`}>
                {/* Header */}
                <div className="p-3">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="text-white mb-0">
                            <span className="text-white">Raha</span>
                            <span className="text-warning">Tele</span>
                        </h5>
                        <Button
                            variant="link"
                            className="text-white-50 p-0 border-0"
                            title="New chat"
                            onClick={handleOpenNewChat}
                        >
                            <i className="bi bi-plus-square fs-4"></i>
                        </Button>
                    </div>

                    {/* Search */}
                    <div className="position-relative mb-3">
                        <i className="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50"></i>
                        <Form.Control
                            type="text"
                            placeholder="Search conversations"
                            value={search}
                            onChange={handleSearchChange}
                            className="bg-transparent text-white rounded-pill ps-5"
                        />
                    </div>

                    {/* Filter Tabs */}
                    <div className="d-flex gap-2">
                        <Button
                            variant={getFilterButtonVariant("all")}
                            size="sm"
                            className={`rounded-pill px-3 ${
                                filterType === "all"
                                    ? "text-white border-0"
                                    : "text-white"
                            }`}
                            onClick={() => handleFilterChange("all")}
                        >
                            All
                        </Button>
                        <Button
                            variant={getFilterButtonVariant("unread")}
                            size="sm"
                            className={`rounded-pill px-3 d-flex align-items-center gap-1 ${
                                filterType === "unread"
                                    ? "text-white border-0"
                                    : "text-white"
                            }`}
                            onClick={() => handleFilterChange("unread")}
                        >
                            <i className="bi bi-envelope"></i>
                            Unread
                        </Button>
                        <Button
                            variant={getFilterButtonVariant("favorites")}
                            size="sm"
                            className={`rounded-pill px-3 d-flex align-items-center gap-1 ${
                                filterType === "favorites"
                                    ? "text-dark border-0"
                                    : "text-white"
                            }`}
                            onClick={() => handleFilterChange("favorites")}
                        >
                            <i
                                className={`bi ${filterType === "favorites" ? "bi-star-fill" : "bi-star"}`}
                            ></i>
                            Favorites
                        </Button>
                    </div>
                </div>

                {/* Conversation List */}
                <div className="flex-grow-1 overflow-auto">
                    {filteredConversations.length === 0 ? (
                        <div className="text-center text-white-50 p-3">
                            <i className="bi bi-chat-dots fs-1 d-block mb-2"></i>
                            <p className="mb-0">
                                {search
                                    ? "No conversations found"
                                    : "No conversations yet"}
                            </p>
                            <Button
                                variant="outline-light"
                                size="sm"
                                className="mt-3"
                                onClick={handleOpenNewChat}
                            >
                                <i className="bi bi-plus-circle me-1"></i>
                                Start a new chat
                            </Button>
                            {!search && archivedCount > 0 && (
                                <div className="mt-3">
                                    <Link
                                        href="/chat/archived"
                                        className="small text-warning text-decoration-none"
                                    >
                                        <i className="bi bi-archive me-1"></i>
                                        View archived chats ({archivedCount})
                                    </Link>
                                </div>
                            )}
                        </div>
                    ) : (
                        <ListGroup variant="flush" className="bg-transparent">
                            {filteredConversations.map((conversation) => (
                                <ListGroup.Item
                                    key={conversation.id}
                                    action
                                    as={Link}
                                    href={`/chat/${conversation.id}`}
                                    className="p-3 py-2 rounded bg-dark text-white border-secondary hover-bg-dark-light"
                                >
                                    <div className="d-flex align-items-center">
                                        {/* Avatar with Status */}
                                        <div className="position-relative flex-shrink-0">
                                            <Image
                                                src={getProfileImage(
                                                    conversation.other_user,
                                                )}
                                                alt={
                                                    conversation.other_user.name
                                                }
                                                roundedCircle
                                                width={56}
                                                height={56}
                                                className="object-fit-cover border border-secondary"
                                            />
                                            {conversation.other_user
                                                .is_online && (
                                                <span
                                                    className="position-absolute bottom-0 end-0 p-1 bg-success border border-2 border-dark rounded-circle"
                                                    style={{
                                                        width: "14px",
                                                        height: "14px",
                                                    }}
                                                >
                                                    <span className="visually-hidden">
                                                        Online
                                                    </span>
                                                </span>
                                            )}
                                        </div>

                                        {/* Content */}
                                        <div className="ms-3 flex-grow-1 min-w-0">
                                            <div className="d-flex align-items-center justify-content-between">
                                                <h6 className="text-white mb-0 fw-medium text-truncate">
                                                    {
                                                        conversation.other_user
                                                            .name
                                                    }
                                                    {conversation.is_favorite && (
                                                        <i className="bi bi-star-fill text-warning ms-2 small"></i>
                                                    )}
                                                    {conversation.last_message
                                                        ?.is_read && (
                                                        <span className="ms-2 text-success-emphasis">
                                                            <i className="bi bi-check-all"></i>
                                                        </span>
                                                    )}
                                                </h6>
                                                {conversation.last_message && (
                                                    <small className="text-white-50 ms-2 flex-shrink-0">
                                                        {getTimeAgo(
                                                            conversation
                                                                .last_message
                                                                .created_at,
                                                        )}
                                                    </small>
                                                )}
                                            </div>

                                            <div className="d-flex align-items-center justify-content-between mt-1">
                                                <small
                                                    className={`text-truncate d-flex align-items-center gap-1 ${
                                                        conversation.unread_count >
                                                        0
                                                            ? "text-white fw-medium"
                                                            : "text-white-50"
                                                    }`}
                                                >
                                                    {conversation.last_message
                                                        ?.type !== "text" && (
                                                        <i
                                                            className={`bi ${
                                                                {
                                                                    image: "bi-image",
                                                                    video: "bi-camera-video",
                                                                    audio: "bi-mic",
                                                                    file: "bi-file",
                                                                    document:
                                                                        "bi-file-text",
                                                                }[
                                                                    conversation
                                                                        .last_message
                                                                        ?.type
                                                                ] ||
                                                                "bi-paperclip"
                                                            }`}
                                                        ></i>
                                                    )}
                                                    {formatMessagePreview(
                                                        conversation,
                                                    )}
                                                </small>
                                                {conversation.unread_count >
                                                    0 && (
                                                    <Badge
                                                        bg="warning"
                                                        pill
                                                        className="ms-2 flex-shrink-0 text-dark"
                                                    >
                                                        {
                                                            conversation.unread_count
                                                        }
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </ListGroup.Item>
                            ))}
                        </ListGroup>
                    )}
                </div>

                {/* Archived footer row — always visible so archived chats are
                    never hidden from the sidebar. */}
                <Link
                    href="/chat/archived"
                    className={`d-flex align-items-center gap-2 px-3 py-3 border-top border-secondary text-decoration-none transition-all ${
                        archivedCount > 0
                            ? "text-warning hover-bg-dark-light"
                            : "text-white-50 hover-text-white"
                    }`}
                    title="View archived chats"
                >
                    <i className="bi bi-archive fs-5"></i>
                    <span className="small fw-medium">Archived chats</span>
                    <span
                        className={`ms-auto badge rounded-pill ${
                            archivedCount > 0 ? "bg-warning text-dark" : "bg-secondary"
                        }`}
                    >
                        {archivedCount}
                    </span>
                </Link>
            </div>

            {/* New Chat Modal */}
            <Modal
                show={showNewChatModal}
                onHide={() => setShowNewChatModal(false)}
                centered
                contentClassName="bg-dark"
            >
                <Modal.Header closeButton className="border-secondary">
                    <Modal.Title className="text-white">New Chat</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <p className="text-white-50 small mb-3">
                        Start a chat with {targetLabel}
                    </p>
                    {/* Search Users */}
                    <div className="position-relative mb-3">
                        <i className="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50"></i>
                        <Form.Control
                            type="text"
                            placeholder={`Search ${targetLabel} by name or email`}
                            value={userSearch}
                            onChange={(e) => setUserSearch(e.target.value)}
                            className="bg-transparent text-white rounded-pill ps-5"
                        />
                    </div>

                    {/* Users List */}
                    <div style={{ maxHeight: "400px", overflowY: "auto" }}>
                        {loading ? (
                            <div className="text-center text-white-50 py-4">
                                <div
                                    className="spinner-border spinner-border-sm me-2"
                                    role="status"
                                >
                                    <span className="visually-hidden">
                                        Loading...
                                    </span>
                                </div>
                                Loading users...
                            </div>
                        ) : filteredUsers.length === 0 ? (
                            <div className="text-center text-white-50 py-4">
                                <i className="bi bi-people fs-1 d-block mb-2"></i>
                                <p className="mb-0">
                                    {userSearch
                                        ? "No users found"
                                        : `No ${targetLabel} available`}
                                </p>
                            </div>
                        ) : (
                            <ListGroup
                                variant="flush"
                                className="bg-transparent"
                            >
                                {filteredUsers.map((user) => (
                                    <ListGroup.Item
                                        key={user.id}
                                        action
                                        onClick={() =>
                                            handleStartConversation(user.id)
                                        }
                                        className="bg-dark text-white border-secondary hover-bg-dark-light"
                                        disabled={creatingUserId === user.id}
                                    >
                                        <div className="d-flex align-items-center">
                                            <Image
                                                src={getProfileImage(user)}
                                                alt={user.name}
                                                roundedCircle
                                                width={48}
                                                height={48}
                                                className="object-fit-cover border border-secondary"
                                            />
                                            <div className="ms-3 flex-grow-1">
                                                <h6 className="text-white mb-1">
                                                    {user.name}
                                                </h6>
                                                {user.email && (
                                                    <small className="text-white-50">
                                                        {user.email}
                                                    </small>
                                                )}
                                            </div>
                                            {creatingUserId === user.id && (
                                                <Spinner
                                                    size="sm"
                                                    variant="light"
                                                    className="ms-2"
                                                />
                                            )}
                                        </div>
                                    </ListGroup.Item>
                                ))}
                            </ListGroup>
                        )}
                    </div>
                </Modal.Body>
            </Modal>
        </>
    );
}

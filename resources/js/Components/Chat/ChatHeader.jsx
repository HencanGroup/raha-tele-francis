import React, { useCallback, useMemo } from "react";
import { router } from "@inertiajs/react";
import { Dropdown, Image, ButtonGroup, Button } from "react-bootstrap";
import { getProfileImage } from "@/Utils/helpers";

// Bootstrap Icons - you'll need to install: npm install bootstrap-icons
// Then import the CSS in your main file: import 'bootstrap-icons/font/bootstrap-icons.css';

export default function ChatHeader({ conversation }) {
    const handleAction = useCallback(
        (action) => {
            router.post(
                `/chat/${conversation.id}/${action}`,
                {},
                {
                    preserveScroll: true,
                },
            );
        },
        [conversation.id],
    );

    const handleCloseChat = useCallback(() => {
        router.get(route("chat.index"));
    }, []);

    const handleDelete = useCallback(() => {
        if (confirm("Are you sure you want to delete this conversation?")) {
            router.delete(`/chat/${conversation.id}`, {
                preserveScroll: true,
            });
        }
    }, [conversation.id]);

    // Memoize the status text to prevent recalculation
    const statusText = useMemo(() => {
        if (conversation.other_user.is_online) {
            return (
                <>
                    <i
                        className="bi bi-circle-fill text-success me-1"
                        style={{ fontSize: "8px" }}
                    ></i>
                    Online
                </>
            );
        }
        return (
            <>
                <i className="bi bi-clock-history me-1"></i>
                Last seen recently
            </>
        );
    }, [conversation.other_user.is_online]);

    // Memoize dropdown items configuration
    const dropdownItems = useMemo(
        () => [
            {
                key: "archive",
                action: "archive",
                icon: conversation.is_archived
                    ? "bi-archive-fill"
                    : "bi-archive",
                label: conversation.is_archived ? "Unarchive" : "Archive",
                description: "conversation",
            },
            {
                key: "mute",
                action: "mute",
                icon: conversation.is_muted
                    ? "bi-bell-slash-fill"
                    : "bi-bell-slash",
                label: conversation.is_muted ? "Unmute" : "Mute",
                description: "conversation",
            },
            {
                key: "block",
                action: "block",
                icon: conversation.is_blocked
                    ? "bi-shield-fill-exclamation"
                    : "bi-shield-exclamation",
                label: conversation.is_blocked ? "Unblock" : "Block",
                description: "user",
            },
        ],
        [
            conversation.is_archived,
            conversation.is_muted,
            conversation.is_blocked,
        ],
    );

    return (
        <div className="d-flex align-items-center justify-content-between p-3 border-bottom border-secondary bg-dark">
            {/* User Info */}
            <div className="d-flex align-items-center">
                <div className="position-relative">
                    <Image
                        src={getProfileImage(conversation.other_user)}
                        alt={conversation.other_user.name}
                        roundedCircle
                        width={44}
                        height={44}
                        className="object-fit-cover border border-secondary"
                    />
                    {conversation.other_user.is_online && (
                        <span
                            className="position-absolute bottom-0 end-0 p-1 bg-success border border-2 border-dark rounded-circle"
                            style={{
                                width: "14px",
                                height: "14px",
                                boxShadow: "0 0 0 2px var(--bs-dark)",
                            }}
                        >
                            <span className="visually-hidden">Online</span>
                        </span>
                    )}
                </div>
                <div className="ms-3">
                    <div className="fw-medium text-white d-flex align-items-center gap-2">
                        {conversation.other_user.name}
                        {conversation.is_favorite && (
                            <i className="bi bi-star-fill text-warning small"></i>
                        )}
                    </div>
                    <small className="text-white-50 d-flex align-items-center">
                        {statusText}
                    </small>
                </div>
            </div>

            {/* Actions */}
            <div className="d-flex gap-1">
                {/* Call Actions - Most Used */}
                <Button
                    variant="link"
                    className="text-white-50 hover-text-white p-2 d-flex align-items-center rounded-circle"
                    title="Start voice call"
                    style={{ transition: "all 0.2s" }}
                >
                    <i className="bi bi-telephone fs-5"></i>
                </Button>

                <Button
                    variant="link"
                    className="text-white-50 hover-text-white p-2 d-flex align-items-center rounded-circle"
                    title="Start video call"
                    style={{ transition: "all 0.2s" }}
                >
                    <i className="bi bi-camera-video fs-5"></i>
                </Button>

                {/* More Options Dropdown */}
                <Dropdown as={ButtonGroup} align="end">
                    <Dropdown.Toggle
                        variant="link"
                        className="text-white-50 hover-text-white p-2 border-0 d-flex align-items-center rounded-circle"
                        title="More options"
                        style={{ transition: "all 0.2s" }}
                    >
                        <i className="bi bi-three-dots-vertical fs-5"></i>
                    </Dropdown.Toggle>

                    <Dropdown.Menu
                        className="bg-dark border-secondary py-2"
                        style={{ minWidth: "220px" }}
                    >
                        {/* Conversation Actions */}
                        {dropdownItems.map((item) => (
                            <Dropdown.Item
                                key={item.key}
                                onClick={() => handleAction(item.action)}
                                className="d-flex align-items-center gap-3 text-white hover-bg-dark-light rounded-0"
                                style={{ transition: "all 0.2s" }}
                            >
                                <i
                                    className={`bi ${item.icon} fs-5`}
                                    style={{ width: "20px" }}
                                ></i>
                                <span>
                                    {item.label}{" "}
                                    <span className="text-white-50">
                                        {item.description}
                                    </span>
                                </span>
                            </Dropdown.Item>
                        ))}

                        <Dropdown.Divider className="border-secondary" />

                        {/* Navigation Actions */}
                        <Dropdown.Item
                            onClick={handleCloseChat}
                            className="d-flex align-items-center gap-3 text-white hover-bg-dark-light rounded-0"
                            style={{ transition: "all 0.2s" }}
                        >
                            <i
                                className="bi bi-x-circle fs-5"
                                style={{ width: "20px" }}
                            ></i>
                            <span>Close chat</span>
                        </Dropdown.Item>

                        {/* Destructive Actions */}
                        <Dropdown.Item
                            onClick={handleDelete}
                            className="d-flex align-items-center gap-3 text-danger hover-bg-danger-light rounded-0"
                            style={{ transition: "all 0.2s" }}
                        >
                            <i
                                className="bi bi-trash fs-5"
                                style={{ width: "20px" }}
                            ></i>
                            <span>Delete conversation</span>
                        </Dropdown.Item>
                    </Dropdown.Menu>
                </Dropdown>
            </div>
        </div>
    );
}

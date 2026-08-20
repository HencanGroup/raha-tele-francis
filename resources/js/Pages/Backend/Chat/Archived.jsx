import React, { useCallback } from "react";
import dayjs from "dayjs";
import { router, Head } from "@inertiajs/react";
import { Image, ListGroup, Button } from "react-bootstrap";
import ChatLayout from "@/Layouts/ChatLayout";
import { getProfileImage } from "@/Utils/helpers";

export default function Archived({ conversations, archived, archivedCount }) {
    const handleUnarchive = useCallback((id) => {
        // Reuses the existing archive toggle route — posting to /archive on an
        // archived conversation flips it back to the main inbox.
        router.post(`/chat/${id}/archive`, {}, { preserveScroll: true });
    }, []);

    return (
        <ChatLayout conversations={conversations} archivedCount={archivedCount}>
            <Head title="Archived Chats" />

            <div className="bg-dark h-100 d-flex flex-column">
                <div className="p-3 border-bottom border-secondary d-flex align-items-center justify-content-between">
                    <h5 className="text-white mb-0">
                        <i className="bi bi-archive me-2"></i>
                        Archived Chats
                    </h5>
                    <small className="text-white-50">
                        {archived.length} archived
                    </small>
                </div>

                <div className="flex-grow-1 overflow-auto p-2">
                    {archived.length === 0 ? (
                        <div className="text-center text-white-50 py-5">
                            <i className="bi bi-archive fs-1 d-block mb-2"></i>
                            <p className="mb-0">No archived chats</p>
                        </div>
                    ) : (
                        <ListGroup variant="flush" className="bg-transparent">
                            {archived.map((conversation) => (
                                <ListGroup.Item
                                    key={conversation.id}
                                    className="bg-dark text-white border-secondary"
                                >
                                    <div className="d-flex align-items-center">
                                        <Image
                                            src={getProfileImage(
                                                conversation.other_user,
                                            )}
                                            alt={conversation.other_user.name}
                                            roundedCircle
                                            width={48}
                                            height={48}
                                            className="object-fit-cover border border-secondary"
                                        />
                                        <div className="ms-3 flex-grow-1 min-w-0">
                                            <div className="d-flex align-items-center justify-content-between">
                                                <h6 className="text-white mb-0 text-truncate">
                                                    {conversation.other_user.name}
                                                </h6>
                                                {conversation.last_message_at && (
                                                    <small className="text-white-50 ms-2 flex-shrink-0">
                                                        {dayjs(
                                                            conversation.last_message_at,
                                                        ).format("MMM D")}
                                                    </small>
                                                )}
                                            </div>
                                            <small className="text-white-50 text-truncate d-block">
                                                {conversation.last_message
                                                    ?.message ||
                                                    "No messages yet"}
                                            </small>
                                        </div>
                                        <Button
                                            variant="outline-warning"
                                            size="sm"
                                            className="ms-2 flex-shrink-0"
                                            onClick={() =>
                                                handleUnarchive(
                                                    conversation.id,
                                                )
                                            }
                                        >
                                            <i className="bi bi-archive me-1"></i>
                                            Unarchive
                                        </Button>
                                    </div>
                                </ListGroup.Item>
                            ))}
                        </ListGroup>
                    )}
                </div>
            </div>
        </ChatLayout>
    );
}
import React, { memo } from "react";
import { Image, Badge } from "react-bootstrap";
import { MessageSquare } from "lucide-react";
import { getProfileImage } from "@/Utils/helpers";

const ConversationItem = memo(
    ({
        conversation,
        isActive,
        auth,
        getOtherUser,
        formatTime,
        getMessageStatus,
        getMessageStatusIcon,
        onSelect,
    }) => {
        const otherUser = getOtherUser(conversation);
        const isUnread = conversation.unread_count > 0;
        const lastMessage = conversation.latest_message;
        const isOnline = otherUser?.online || false;

        if (!otherUser) return null;

        return (
            <div
                className={`p-2 rounded-3 chat-conversation cursor-pointer ${
                    isActive ? "bg-dark" : "bg-transparent"
                } ${isUnread ? "bg-warning-10" : ""}`}
                onClick={() => onSelect(conversation)}
            >
                <div className="d-flex align-items-center">
                    <div className="position-relative me-3">
                        <Image
                            src={getProfileImage(otherUser?.profile)}
                            alt={otherUser?.name}
                            className="rounded-circle"
                            style={{ width: 48, height: 48 }}
                            loading="lazy"
                        />
                        <span
                            className={`position-absolute bottom-0 end-0 rounded-circle border border-dark ${
                                isOnline ? "bg-success" : "bg-secondary"
                            }`}
                            style={{ width: 12, height: 12 }}
                        />
                    </div>
                    <div className="flex-grow-1">
                        <div className="d-flex justify-content-between align-items-start">
                            <h6 className="text-white mb-1">
                                {otherUser?.name || "Unknown User"}
                            </h6>
                            <small className="text-white-50">
                                {formatTime(
                                    conversation.last_message_at ||
                                        conversation.updated_at
                                )}
                            </small>
                        </div>
                        <div className="d-flex align-items-center">
                            {lastMessage?.sender_id === auth.user.id && (
                                <span className="me-1">
                                    {getMessageStatusIcon(
                                        getMessageStatus(lastMessage)
                                    )}
                                </span>
                            )}
                            <p
                                className="text-white-50 mb-0 text-truncate"
                                style={{ fontSize: "0.875rem" }}
                            >
                                {lastMessage
                                    ? `${
                                          lastMessage.sender_id === auth.user.id
                                              ? "You: "
                                              : ""
                                      }${
                                          lastMessage.message?.substring(
                                              0,
                                              30
                                          ) || ""
                                      }${
                                          lastMessage.message?.length > 30
                                              ? "..."
                                              : ""
                                      }`
                                    : "Start chatting"}
                            </p>
                        </div>
                    </div>
                    {isUnread && (
                        <Badge
                            bg="warning"
                            className="ms-2 text-dark"
                            style={{ minWidth: 20, height: 20 }}
                        >
                            {conversation.unread_count > 99
                                ? "99+"
                                : conversation.unread_count}
                        </Badge>
                    )}
                </div>
            </div>
        );
    }
);

export default ConversationItem;

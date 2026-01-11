import React, { memo } from "react";
import { Image, Button, Badge } from "react-bootstrap";
import { X, Phone, ShieldAlert, Ban, MessageCircle, Clock } from "lucide-react";
import { getProfileImage, hidePhoneNumber } from "@/Utils/helpers";

const ProfileInfo = memo(
    ({
        otherUser,
        formatTime,
        auth,
        getMessageStatus,
        getMessageStatusIcon,
        onClose,
    }) => {
        if (!otherUser) {
            return (
                <div className="p-3 text-white-50">
                    No profile information available
                </div>
            );
        }

        return (
            <div
                className="h-100 d-flex flex-column p-2 border-start border-dark"
                style={{ background: "#1a1a1a" }}
            >
                {/* HEADER */}
                <div className="text-end">
                    <Button
                        variant="link"
                        className="text-white-50 p-0"
                        onClick={onClose}
                    >
                        <X size={18} />
                    </Button>
                </div>

                {/* CONTENT */}
                <div className="flex-grow-1 overflow-auto p-3">
                    {/* AVATAR */}
                    <div className="text-center mb-3">
                        <Image
                            src={getProfileImage(otherUser.profile)}
                            roundedCircle
                            style={{
                                width: 96,
                                height: 96,
                                objectFit: "cover",
                            }}
                        />
                        <h5 className="mt-3 mb-1">
                            {otherUser.name || "Unknown User"}
                        </h5>

                        {otherUser.online ? (
                            <Badge bg="success">Online</Badge>
                        ) : (
                            <small className="text-white-50 d-block">
                                <Clock size={12} className="me-1" />
                                Last seen {formatTime(otherUser.last_seen)}
                            </small>
                        )}
                    </div>

                    {/* ABOUT */}
                    {otherUser.bio && (
                        <div className="mb-3">
                            <small className="text-white-50">About</small>
                            <p className="mb-0">{otherUser.bio}</p>
                        </div>
                    )}

                    {/* PHONE */}
                    {otherUser.phone && (
                        <div className="mb-3">
                            <small className="text-white-50">Phone</small>
                            <div className="d-flex align-items-center mt-1">
                                <Phone size={14} className="me-2" />
                                <span>
                                    {auth?.user
                                        ? hidePhoneNumber(otherUser.phone)
                                        : "Hidden"}
                                </span>
                            </div>
                        </div>
                    )}

                    {/* STATS */}
                    <div className="mb-3">
                        <small className="text-white-50">Stats</small>
                        <div className="mt-2 d-flex gap-3">
                            <div>
                                <small className="text-white-50">
                                    Messages
                                </small>
                                <div>
                                    {getMessageStatus?.(otherUser.id) || "—"}
                                </div>
                            </div>

                            <div>
                                <small className="text-white-50">Status</small>
                                <div>
                                    {getMessageStatusIcon?.(otherUser.id) ||
                                        "—"}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ACTIONS */}
                <div className="p-3">
                    <Button
                        variant="outline-warning"
                        size="sm"
                        className="w-100 mb-2"
                    >
                        <ShieldAlert size={14} className="me-2" />
                        Report User
                    </Button>

                    <Button
                        variant="outline-danger"
                        size="sm"
                        className="w-100"
                    >
                        <Ban size={14} className="me-2" />
                        Block User
                    </Button>
                </div>
            </div>
        );
    }
);

export default ProfileInfo;

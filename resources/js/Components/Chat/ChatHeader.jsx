import React, { memo } from "react";
import { Image, Button, ButtonGroup, Dropdown } from "react-bootstrap";
import {
    ChevronLeft,
    PhoneCall,
    Video,
    MoreVertical,
    User,
    X,
    Trash2,
    EyeOff,
    Flag,
    RefreshCw,
    Ban,
    Circle,
} from "lucide-react";
import { formatTime, getProfileImage } from "@/Utils/helpers";

const ChatHeader = memo(
    ({
        mobileView,
        activeOtherUser,
        onCloseChat,
        showProfileInfo,
        setShowProfileInfo,
    }) => {
        const toggleProfile = () => {
            setShowProfileInfo((prev) => !prev);
        };

        return (
            <div
                className="p-2 border-bottom border-dark d-flex align-items-center justify-content-between"
                style={{ background: "#1a1a1a" }}
            >
                {/* LEFT */}
                <div className="d-flex align-items-center p-2">
                    {mobileView && (
                        <Button
                            variant="link"
                            className="text-white me-3 p-1"
                            onClick={onCloseChat}
                        >
                            <ChevronLeft size={24} />
                        </Button>
                    )}

                    {/* USER INFO */}
                    <div
                        className="d-flex align-items-center cursor-pointer"
                        role="button"
                        onClick={toggleProfile}
                    >
                        <Image
                            src={getProfileImage(activeOtherUser)}
                            alt={activeOtherUser?.name}
                            className="rounded-circle me-3"
                            style={{ width: 40, height: 40 }}
                        />
                        <div>
                            <h6 className="text-white mb-0">
                                {activeOtherUser?.name || "Unknown User"}
                            </h6>
                            {activeOtherUser?.last_seen && (
                                <small>
                                    {activeOtherUser?.is_online ? (
                                        <div className="d-flex align-items-center">
                                            <Circle
                                                size={10}
                                                fill="#22c55e"
                                                stroke="none"
                                                className="me-1"
                                            />{" "}
                                            Online
                                        </div>
                                    ) : (
                                        `Last seen ${formatTime(
                                            activeOtherUser?.last_seen
                                        )}`
                                    )}
                                </small>
                            )}
                        </div>
                    </div>
                </div>

                {/* ACTIONS */}
                <ButtonGroup className="d-flex align-items-center">
                    <Button
                        variant="link"
                        className="text-white-50 p-2 rounded"
                    >
                        <PhoneCall size={20} />
                    </Button>

                    <Button
                        variant="link"
                        className="text-white-50 p-2 rounded"
                    >
                        <Video size={20} />
                    </Button>

                    <Dropdown align="end">
                        <Dropdown.Toggle
                            variant="link"
                            className="text-white-50 p-2 rounded"
                        >
                            <MoreVertical size={20} />
                        </Dropdown.Toggle>

                        <Dropdown.Menu>
                            <Dropdown.Item onClick={toggleProfile}>
                                {showProfileInfo ? (
                                    <>
                                        <EyeOff size={16} className="me-2" />
                                        Hide Profile
                                    </>
                                ) : (
                                    <>
                                        <User size={16} className="me-2" />
                                        View Profile
                                    </>
                                )}
                            </Dropdown.Item>

                            <Dropdown.Item onClick={onCloseChat}>
                                <X size={16} className="me-2" />
                                Close Chat
                            </Dropdown.Item>

                            <Dropdown.Item>
                                <Flag size={16} className="me-2" />
                                Report
                            </Dropdown.Item>

                            <Dropdown.Item>
                                <Ban size={16} className="me-2" />
                                Block
                            </Dropdown.Item>

                            <Dropdown.Item>
                                <RefreshCw size={16} className="me-2" />
                                Clear Chat
                            </Dropdown.Item>

                            <Dropdown.Item className="text-danger">
                                <Trash2 size={16} className="me-2" />
                                Delete Chat
                            </Dropdown.Item>
                        </Dropdown.Menu>
                    </Dropdown>
                </ButtonGroup>
            </div>
        );
    }
);

export default ChatHeader;

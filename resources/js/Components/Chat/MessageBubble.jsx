import React, { memo } from "react";
import { File, Check, CheckCheck } from "lucide-react";

const MessageBubble = memo(
    ({ message, auth, formatTime, getMessageStatus, getMessageStatusIcon }) => {
        const isSent = message.sender_id === auth.user.id;
        const status = getMessageStatus(message);
        const time = formatTime(message.created_at);

        return (
            <div
                className={`d-flex mb-3 ${
                    isSent ? "justify-content-end" : "justify-content-start"
                }`}
            >
                <div
                    className={`p-1 px-2 rounded-4 ${
                        isSent ? "bg-warning" : "bg-dark"
                    }`}
                    style={{
                        maxWidth: "65%",
                        borderBottomRightRadius: isSent ? "4px" : "16px",
                        borderBottomLeftRadius: isSent ? "16px" : "4px",
                    }}
                >
                    <div className="mb-1 text-break">
                        {message.message}
                        {message.attachment && (
                            <div className="mt-2 d-flex align-items-center">
                                <File size={16} className="me-2" />
                                <small>
                                    {message.attachment_name ||
                                        message.attachment}
                                </small>
                            </div>
                        )}
                    </div>
                    <div className="d-flex justify-content-end align-items-center">
                        <small
                            className={`${
                                isSent ? "text-dark" : "text-white-50"
                            } me-1`}
                        >
                            {time}
                        </small>
                        {isSent && (
                            <span className="ms-1">
                                {getMessageStatusIcon(status)}
                            </span>
                        )}
                    </div>
                </div>
            </div>
        );
    }
);

const getMessageStatusIcon = (status) => {
    switch (status) {
        case "sent":
            return <Check size={16} />;
        case "delivered":
            return <CheckCheck size={16} />;
        case "read":
            return <CheckCheck size={16} className="text-primary" />;
        default:
            return null;
    }
};

export { getMessageStatusIcon };
export default MessageBubble;

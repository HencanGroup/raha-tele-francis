import React from "react";
import { Button } from "react-bootstrap";
import { MessageSquare, UserPlus } from "lucide-react";
import { Link } from "@inertiajs/react";

export const EmptyConversationList = () => (
    <div className="text-center py-5">
        <div className="rounded-circle bg-secondary p-4 mb-3 d-inline-block">
            <MessageSquare size={30} className="text-white-50" />
        </div>
        <h5 className="fw-bold mb-2 text-white">No conversations yet</h5>
        <p className="text-white-50 mb-4">Start chatting with nearby escorts</p>
        <Button
            variant="warning"
            as={Link}
            href={route("escort.index")}
            className="rounded-pill"
        >
            <UserPlus size={18} className="me-2" />
            Find someone to chat with
        </Button>
    </div>
);

export const EmptyMessageState = ({ otherUserName }) => (
    <div className="h-100 d-flex flex-column align-items-center justify-content-center">
        <div className="bg-secondary rounded-circle p-4 mb-3">
            <MessageSquare size={32} className="text-white-50" />
        </div>
        <h5 className="text-white mb-2">No messages yet</h5>
        <p className="text-white-50 text-center">
            Send your first message to {otherUserName}
        </p>
    </div>
);

export const EmptyChatState = () => (
    <div className="h-100 d-flex flex-column align-items-center justify-content-center">
        <div className="bg-secondary rounded-circle p-4 mb-3">
            <MessageSquare size={32} className="text-white-50" />
        </div>
        <h4 className="text-white mb-2">Select a conversation</h4>
        <p className="text-white-50">
            Choose a chat from the list to start messaging
        </p>
    </div>
);

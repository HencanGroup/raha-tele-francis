import React from "react";
import ChatLayout from "@/Layouts/ChatLayout";

import { ChatProvider } from "@/Components/Contexts/ChatContext";
import { Head } from "@inertiajs/react";

export default function Index({ conversations }) {
    return (
        <ChatLayout conversations={conversations}>
            <Head title="Messages" />

            <ChatProvider>
                <div className="bg-dark d-flex align-items-center justify-content-center h-100 text-secondary">
                    <div className="text-center">
                        <svg
                            className="mx-auto mb-3"
                            width="48"
                            height="48"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            style={{ color: "#6c757d" }}
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                            />
                        </svg>
                        <p className="mb-0">
                            Select a conversation to start chatting
                        </p>
                    </div>
                </div>
            </ChatProvider>
        </ChatLayout>
    );
}

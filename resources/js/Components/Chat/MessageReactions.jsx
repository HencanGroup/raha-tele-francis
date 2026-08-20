// resources/js/Components/Chat/MessageReactions.jsx
// Renders the reaction row under a message bubble: a preset set of emoji
// buttons with per-emoji counts, highlighting the current user's reaction.
// Tapping the active emoji removes it; tapping another one overwrites it
// (the backend upserts by user id).

import React from "react";
import { REACTION_EMOJIS } from "@/Utils/chat";

export default function MessageReactions({ message, myUserId, onToggle }) {
    const reactions = message.reactions || {};

    // Count occurrences of each emoji across all users.
    const counts = {};
    Object.values(reactions).forEach((emoji) => {
        counts[emoji] = (counts[emoji] || 0) + 1;
    });

    const myReaction = reactions[myUserId];

    return (
        <div className="message-reactions d-flex align-items-center gap-1 mt-1">
            {REACTION_EMOJIS.map((emoji) => {
                const count = counts[emoji] || 0;
                const active = myReaction === emoji;

                return (
                    <button
                        key={emoji}
                        type="button"
                        onClick={() => onToggle(message, emoji)}
                        title={active ? "Remove reaction" : `React ${emoji}`}
                        aria-pressed={active}
                        className={`message-reaction ${active ? "active" : ""}`}
                    >
                        <span className="reaction-emoji">{emoji}</span>
                        {count > 0 && (
                            <span className="reaction-count">{count}</span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
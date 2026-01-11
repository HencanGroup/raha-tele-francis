import { useCallback, useMemo } from "react";

export const useConversationHelpers = (auth) => {
    const formatTimeMemo = useCallback((timeString) => {
        if (!timeString) return "";
        const date = new Date(timeString);
        return date.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });
    }, []);

    const getOtherUser = useCallback(
        (conversation) => {
            if (!conversation || !auth?.user) return null;
            return conversation.user_one_id === auth.user.id
                ? conversation.user_two
                : conversation.user_one;
        },
        [auth?.user?.id]
    );

    const getMessageStatus = useCallback((message) => {
        if (message.read_at) return "read";
        if (message.delivered_at) return "delivered";
        return "sent";
    }, []);

    return {
        formatTimeMemo,
        getOtherUser,
        getMessageStatus,
    };
};

export const useFilteredConversations = (
    conversations,
    searchTerm,
    activeFilter,
    getOtherUser
) => {
    return useMemo(() => {
        return conversations
            .filter((conv) => {
                const otherUser = getOtherUser(conv);
                if (!otherUser) return false;
                if (!searchTerm) return true;
                return (
                    otherUser.name
                        ?.toLowerCase()
                        .includes(searchTerm.toLowerCase()) ||
                    conv.latest_message?.message
                        ?.toLowerCase()
                        .includes(searchTerm.toLowerCase())
                );
            })
            .filter((conv) => {
                switch (activeFilter) {
                    case "Unread":
                        return conv.unread_count > 0;
                    case "Favourites":
                        return conv.is_favorite;
                    default:
                        return true;
                }
            });
    }, [conversations, searchTerm, activeFilter, getOtherUser]);
};

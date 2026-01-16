// resources/js/Utils/messageReducer.js

export const messageReducer = (state, action) => {
    switch (action.type) {
        case "SET_MESSAGES":
            return {
                ...state,
                messages: action.payload || [],
            };

        case "ADD_OPTIMISTIC": {
            const { conversationId, message } = action.payload;

            return {
                ...state,
                optimisticMessages: {
                    ...state.optimisticMessages,
                    [conversationId]: [
                        ...(state.optimisticMessages[conversationId] || []),
                        message,
                    ],
                },
            };
        }

        case "REPLACE_OPTIMISTIC": {
            const { conversationId, client_id, message } = action.payload;

            return {
                ...state,
                messages: [
                    ...state.messages.filter((m) => m.client_id !== client_id),
                    message,
                ],
                optimisticMessages: {
                    ...state.optimisticMessages,
                    [conversationId]:
                        state.optimisticMessages[conversationId]?.filter(
                            (m) => m.client_id !== client_id
                        ) || [],
                },
            };
        }

        case "UPDATE_OPTIMISTIC_STATUS": {
            const { conversationId, client_id, status } = action.payload;

            return {
                ...state,
                optimisticMessages: {
                    ...state.optimisticMessages,
                    [conversationId]:
                        state.optimisticMessages[conversationId]?.map((m) =>
                            m.client_id === client_id ? { ...m, status } : m
                        ) || [],
                },
            };
        }

        case "UPDATE_MESSAGE_STATUS": {
            const { messageId, status, readAt } = action.payload;

            return {
                ...state,
                messages: state.messages.map((m) =>
                    m.id === messageId
                        ? {
                              ...m,
                              status,
                              read_at: readAt ?? m.read_at,
                          }
                        : m
                ),
            };
        }

        default:
            return state;
    }
};

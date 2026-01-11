export const messageReducer = (state, action) => {
    switch (action.type) {
        case "ADD_MESSAGES":
            return {
                ...state,
                messages: [...state.messages, ...action.payload]
                    .filter(
                        (msg, index, self) =>
                            index === self.findIndex((m) => m.id === msg.id)
                    )
                    .sort(
                        (a, b) =>
                            new Date(a.created_at) - new Date(b.created_at)
                    ),
            };
        case "SET_MESSAGES":
            return {
                ...state,
                messages: action.payload,
            };
        case "ADD_OPTIMISTIC":
            return {
                ...state,
                optimisticMessages: {
                    ...state.optimisticMessages,
                    [action.payload.conversationId]: [
                        ...(state.optimisticMessages[
                            action.payload.conversationId
                        ] || []),
                        action.payload.message,
                    ],
                },
            };
        case "REMOVE_OPTIMISTIC":
            return {
                ...state,
                optimisticMessages: {
                    ...state.optimisticMessages,
                    [action.payload.conversationId]: (
                        state.optimisticMessages[
                            action.payload.conversationId
                        ] || []
                    ).filter((m) => m.tempId !== action.payload.tempId),
                },
            };
        case "MARK_AS_READ":
            return {
                ...state,
                messages: state.messages.map((msg) =>
                    action.payload.messageIds.includes(msg.id)
                        ? {
                              ...msg,
                              is_read: true,
                              read_at: new Date().toISOString(),
                          }
                        : msg
                ),
            };
        default:
            return state;
    }
};

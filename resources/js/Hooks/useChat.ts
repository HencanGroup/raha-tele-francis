import { useState, useEffect, useCallback, useRef } from 'react';
import { usePage, router } from '@inertiajs/react';
import axios from 'axios';
import { ChatMessage, ChatConversation, ApiResponse, MessageFormData } from '@/types/chat';

export const useChat = () => {
    const { props } = usePage();
    const { auth } = props as any;

    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [conversations, setConversations] = useState<ChatConversation[]>([]);
    const [activeConversation, setActiveConversation] = useState<ChatConversation | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [hasMore, setHasMore] = useState(true);
    const [page, setPage] = useState(1);
    const pageRef = useRef(1);

    const loadConversations = useCallback(async (page = 1) => {
        try {
            const response = await axios.get<ApiResponse<ChatConversation[]>>('/api/conversations', {
                params: { page }
            });

            if (response.data.success) {
                if (page === 1) {
                    setConversations(response.data.data || []);
                } else {
                    setConversations(prev => [...prev, ...(response.data.data || [])]);
                }
                setHasMore((response.data.meta?.current_page || 0) < (response.data.meta?.last_page || 1));
                pageRef.current = page;
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }, []);

    const loadMessages = useCallback(async (conversationId: number, page = 1) => {
        try {
            const response = await axios.get<ApiResponse<ChatMessage[]>>(`/api/conversations/${conversationId}/messages`, {
                params: { page }
            });

            if (response.data.success) {
                if (page === 1) {
                    setMessages(response.data.data || []);
                } else {
                    setMessages(prev => [...(response.data.data || []), ...prev]);
                }
                setHasMore((response.data.meta?.current_page || 0) < (response.data.meta?.last_page || 1));
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }, []);

    const sendMessage = useCallback(async (formData: MessageFormData) => {
        try {
            const payload = new FormData();
            payload.append('message', formData.message);
            payload.append('conversation_id', formData.conversation_id.toString());

            if (formData.attachment) {
                payload.append('attachment', formData.attachment);
            }

            if (formData.reply_to_id) {
                payload.append('reply_to_id', formData.reply_to_id.toString());
            }

            if (formData.type) {
                payload.append('type', formData.type);
            }

            const response = await axios.post<ApiResponse<ChatMessage>>('/api/messages', payload, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            if (response.data.success && response.data.data) {
                setMessages(prev => [...prev, response.data.data!]);

                // Update conversation list
                setConversations(prev =>
                    prev.map(conv =>
                        conv.id === formData.conversation_id
                            ? {
                                ...conv,
                                last_message_at: new Date().toISOString(),
                                latest_message: response.data.data!
                            }
                            : conv
                    ).sort((a, b) =>
                        new Date(b.last_message_at).getTime() - new Date(a.last_message_at).getTime()
                    )
                );

                return { success: true, message: response.data.data };
            }

            return { success: false, error: response.data.message };
        } catch (error: any) {
            console.error('Error sending message:', error);
            return {
                success: false,
                error: error.response?.data?.message || 'Failed to send message'
            };
        }
    }, []);

    const markAsRead = useCallback(async (conversationId: number) => {
        try {
            await axios.post(`/api/conversations/${conversationId}/read`);

            // Update local state
            setConversations(prev =>
                prev.map(conv =>
                    conv.id === conversationId
                        ? { ...conv, unread_count: 0 }
                        : conv
                )
            );

            setMessages(prev =>
                prev.map(msg =>
                    msg.sender_id !== auth.user.id
                        ? { ...msg, is_read: true, read_at: new Date().toISOString() }
                        : msg
                )
            );
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    }, [auth.user.id]);

    const createConversation = useCallback(async (userId: number) => {
        try {
            const response = await axios.post<ApiResponse<ChatConversation>>('/api/conversations', {
                user_two_id: userId
            });

            if (response.data.success && response.data.data) {
                setConversations(prev => [response.data.data!, ...prev]);
                setActiveConversation(response.data.data);
                return { success: true, conversation: response.data.data };
            }

            return { success: false, error: response.data.message };
        } catch (error: any) {
            console.error('Error creating conversation:', error);
            return {
                success: false,
                error: error.response?.data?.message || 'Failed to create conversation'
            };
        }
    }, []);

    const deleteMessage = useCallback(async (messageId: number) => {
        try {
            await axios.delete(`/api/messages/${messageId}`);

            setMessages(prev =>
                prev.map(msg =>
                    msg.id === messageId
                        ? { ...msg, is_deleted: true, deleted_at: new Date().toISOString() }
                        : msg
                )
            );

            return { success: true };
        } catch (error: any) {
            console.error('Error deleting message:', error);
            return {
                success: false,
                error: error.response?.data?.message || 'Failed to delete message'
            };
        }
    }, []);

    const updateMessage = useCallback(async (messageId: number, content: string) => {
        try {
            const response = await axios.put<ApiResponse<ChatMessage>>(`/api/messages/${messageId}`, {
                message: content
            });

            if (response.data.success && response.data.data) {
                setMessages(prev =>
                    prev.map(msg =>
                        msg.id === messageId
                            ? response.data.data!
                            : msg
                    )
                );

                return { success: true, message: response.data.data };
            }

            return { success: false, error: response.data.message };
        } catch (error: any) {
            console.error('Error updating message:', error);
            return {
                success: false,
                error: error.response?.data?.message || 'Failed to update message'
            };
        }
    }, []);

    const loadMoreMessages = useCallback(() => {
        if (activeConversation && hasMore && !isLoading) {
            const nextPage = page + 1;
            setPage(nextPage);
            loadMessages(activeConversation.id, nextPage);
        }
    }, [activeConversation, hasMore, isLoading, page, loadMessages]);

    const refreshConversations = useCallback(() => {
        loadConversations(1);
    }, [loadConversations]);

    return {
        messages,
        conversations,
        activeConversation,
        isLoading,
        hasMore,
        setActiveConversation,
        loadConversations,
        loadMessages,
        sendMessage,
        markAsRead,
        createConversation,
        deleteMessage,
        updateMessage,
        loadMoreMessages,
        refreshConversations,
    };
};
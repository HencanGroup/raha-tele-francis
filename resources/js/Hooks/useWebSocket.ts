// /resources/js/Hooks/useWebSocket.ts (simplified)
import { useState, useEffect, useCallback, useRef } from 'react';

interface UseWebSocketOptions {
    url?: string;
    onMessage?: (data: any) => void;
    onOpen?: () => void;
    onClose?: () => void;
    onError?: (error: Event) => void;
    autoConnect?: boolean;
}

export const useWebSocket = (options: UseWebSocketOptions = {}) => {
    const {
        url: providedUrl,
        onMessage,
        onOpen,
        onClose,
        onError,
        autoConnect = true,
    } = options;

    const [socket, setSocket] = useState<WebSocket | null>(null);
    const [isConnected, setIsConnected] = useState(false);
    const [lastMessage, setLastMessage] = useState<any>(null);
    const [error, setError] = useState<Error | null>(null);

    const socketRef = useRef<WebSocket | null>(null);
    const isMountedRef = useRef(true);

    // Get WebSocket URL
    const getWebSocketUrl = useCallback(() => {
        // Use provided URL if available
        if (providedUrl) return providedUrl;

        // Fallback: construct from current URL
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.host;
        const path = '/ws'; // Default WebSocket path

        return `${protocol}//${host}${path}`;
    }, [providedUrl]);

    const connect = useCallback(() => {
        if (!isMountedRef.current) return;

        disconnect();

        const wsUrl = getWebSocketUrl();
        console.log('Connecting to WebSocket:', wsUrl);

        try {
            const ws = new WebSocket(wsUrl);
            socketRef.current = ws;

            ws.onopen = () => {
                if (!isMountedRef.current) return;
                setIsConnected(true);
                setError(null);
                console.log('WebSocket connected successfully');
                onOpen?.();
            };

            ws.onmessage = (event) => {
                if (!isMountedRef.current) return;
                try {
                    const data = JSON.parse(event.data);
                    setLastMessage(data);
                    onMessage?.(data);
                } catch (err) {
                    console.error('Failed to parse message:', err);
                }
            };

            ws.onerror = (event) => {
                if (!isMountedRef.current) return;
                console.error('WebSocket error:', event);
                setError(new Error('WebSocket connection error'));
                onError?.(event);
            };

            ws.onclose = (event) => {
                if (!isMountedRef.current) return;
                setIsConnected(false);
                console.log('WebSocket disconnected');
                onClose?.();
            };

            setSocket(ws);
        } catch (err) {
            console.error('Failed to create WebSocket:', err);
            setError(err instanceof Error ? err : new Error('Connection failed'));
        }
    }, [getWebSocketUrl, onMessage, onOpen, onClose, onError]);

    const disconnect = useCallback(() => {
        if (socketRef.current) {
            socketRef.current.close();
            socketRef.current = null;
        }
        setSocket(null);
        setIsConnected(false);
    }, []);

    const sendMessage = useCallback((data: any) => {
        if (socketRef.current?.readyState === WebSocket.OPEN) {
            const message = typeof data === 'string' ? data : JSON.stringify(data);
            socketRef.current.send(message);
            return true;
        }
        console.warn('Cannot send message: WebSocket not connected');
        return false;
    }, []);

    useEffect(() => {
        isMountedRef.current = true;

        if (autoConnect) {
            connect();
        }

        return () => {
            isMountedRef.current = false;
            disconnect();
        };
    }, [autoConnect, connect, disconnect]);

    return {
        socket,
        isConnected,
        connect,
        disconnect,
        sendMessage,
        lastMessage,
        error,
    };
};

export default useWebSocket;
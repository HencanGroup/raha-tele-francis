import { useState, useRef, useCallback, useEffect } from "react";
import debounce from "lodash/debounce";

/**
 * Enhanced typing detection hook with real-time monitoring
 * Tracks input length changes, cursor position, and composition events
 */
export const useTypingDetection = (options = {}) => {
    const {
        onTypingStart,
        onTypingStop,
        idleTimeout = 1500,
        debounceTime = 300,
        minCharsForTyping = 1,
    } = options;

    const [isTyping, setIsTyping] = useState(false);
    const [inputLength, setInputLength] = useState(0);
    const [cursorPosition, setCursorPosition] = useState(0);
    const [isComposing, setIsComposing] = useState(false);

    const typingTimeoutRef = useRef(null);
    const lastActivityTimeRef = useRef(Date.now());
    const inputRef = useRef(null);
    const lastValueRef = useRef("");
    const lastCursorRef = useRef(0);

    // Clear typing timeout
    const clearTypingTimeout = useCallback(() => {
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
            typingTimeoutRef.current = null;
        }
    }, []);

    // Handle typing stop
    const handleTypingStop = useCallback(() => {
        if (isTyping) {
            setIsTyping(false);
            onTypingStop?.();
            console.log("Typing stopped");
        }
    }, [isTyping, onTypingStop]);

    // Schedule typing stop after idle
    const scheduleTypingStop = useCallback(() => {
        clearTypingTimeout();
        typingTimeoutRef.current = setTimeout(handleTypingStop, idleTimeout);
    }, [handleTypingStop, idleTimeout, clearTypingTimeout]);

    // Debounced typing start detection
    const debouncedTypingStart = useCallback(
        debounce(() => {
            if (!isTyping) {
                setIsTyping(true);
                onTypingStart?.();
                console.log("Typing started");
            }
        }, debounceTime),
        [isTyping, onTypingStart, debounceTime]
    );

    // Detect typing activity
    const detectTypingActivity = useCallback(
        (event) => {
            const currentValue = event.target.value;
            const currentCursor = event.target.selectionStart;
            const currentTime = Date.now();

            // Calculate changes
            const hasContentChange = currentValue !== lastValueRef.current;
            const hasCursorMovement = currentCursor !== lastCursorRef.current;
            const timeSinceLastActivity =
                currentTime - lastActivityTimeRef.current;

            // Update refs
            lastValueRef.current = currentValue;
            lastCursorRef.current = currentCursor;
            lastActivityTimeRef.current = currentTime;

            // Update state
            setInputLength(currentValue.length);
            setCursorPosition(currentCursor);

            // Determine if this is significant activity
            const isSignificantActivity =
                (hasContentChange || hasCursorMovement) &&
                timeSinceLastActivity > 50; // 50ms throttle

            if (isSignificantActivity) {
                // Check if we should trigger typing start
                if (currentValue.length >= minCharsForTyping && !isComposing) {
                    debouncedTypingStart();
                }

                // Reset idle timeout
                scheduleTypingStop();
            }

            // If input is cleared, stop typing
            if (currentValue.length === 0 && isTyping) {
                handleTypingStop();
            }
        },
        [
            isTyping,
            isComposing,
            debouncedTypingStart,
            scheduleTypingStop,
            handleTypingStop,
            minCharsForTyping,
        ]
    );

    // Handle composition start (IME input)
    const handleCompositionStart = useCallback(() => {
        setIsComposing(true);
        if (!isTyping) {
            setIsTyping(true);
            onTypingStart?.();
        }
        scheduleTypingStop();
    }, [isTyping, onTypingStart, scheduleTypingStop]);

    // Handle composition end (IME input completed)
    const handleCompositionEnd = useCallback(() => {
        setIsComposing(false);
        // Check if user is still typing after IME composition
        setTimeout(() => {
            if (inputRef.current) {
                detectTypingActivity({ target: inputRef.current });
            }
        }, 100);
    }, [detectTypingActivity]);

    // Handle input focus
    const handleFocus = useCallback(() => {
        lastActivityTimeRef.current = Date.now();
        // Reset detection state
        if (inputRef.current) {
            lastValueRef.current = inputRef.current.value;
            lastCursorRef.current = inputRef.current.selectionStart;
        }
    }, []);

    // Handle input blur
    const handleBlur = useCallback(() => {
        // Only stop typing if input is empty
        if (inputRef.current && inputRef.current.value.length === 0) {
            handleTypingStop();
        }
    }, [handleTypingStop]);

    // Reset typing state
    const resetTyping = useCallback(() => {
        setIsTyping(false);
        setInputLength(0);
        setCursorPosition(0);
        lastValueRef.current = "";
        lastCursorRef.current = 0;
        clearTypingTimeout();
        onTypingStop?.();
    }, [clearTypingTimeout, onTypingStop]);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            clearTypingTimeout();
            debouncedTypingStart.cancel();
        };
    }, [clearTypingTimeout, debouncedTypingStart]);

    return {
        isTyping,
        inputLength,
        cursorPosition,
        isComposing,
        inputRef,
        detectTypingActivity,
        handleCompositionStart,
        handleCompositionEnd,
        handleFocus,
        handleBlur,
        resetTyping,
    };
};

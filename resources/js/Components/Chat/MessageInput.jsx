import React, { useState, useRef, useEffect, useCallback } from "react";
import { Form, Button, InputGroup } from "react-bootstrap";
import EmojiPicker from "emoji-picker-react";

export default function MessageInput({
    onSendMessage,
    onTyping,
    disabled = false,
}) {
    const [message, setMessage] = useState("");
    const [isTyping, setIsTyping] = useState(false);
    const [showEmojiPicker, setShowEmojiPicker] = useState(false);
    const typingTimeoutRef = useRef(null);
    const inputRef = useRef(null);
    const emojiPickerRef = useRef(null);

    useEffect(() => {
        // Auto-focus input
        inputRef.current?.focus();
    }, []);

    // Handle click outside to close emoji picker
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                emojiPickerRef.current &&
                !emojiPickerRef.current.contains(event.target)
            ) {
                setShowEmojiPicker(false);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);
        return () => {
            document.removeEventListener("mousedown", handleClickOutside);
        };
    }, []);

    const handleTyping = useCallback(() => {
        if (!isTyping) {
            setIsTyping(true);
            onTyping(true);
        }

        // Clear existing timeout
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }

        // Set new timeout
        typingTimeoutRef.current = setTimeout(() => {
            setIsTyping(false);
            onTyping(false);
        }, 1000);
    }, [isTyping, onTyping]);

    const handleSubmit = useCallback(
        (e) => {
            e.preventDefault();

            if (message.trim() && !disabled) {
                onSendMessage(message.trim());
                setMessage("");

                // Reset typing indicator
                if (typingTimeoutRef.current) {
                    clearTimeout(typingTimeoutRef.current);
                }
                setIsTyping(false);
                onTyping(false);
            }
        },
        [message, disabled, onSendMessage, onTyping],
    );

    const handleKeyDown = useCallback(
        (e) => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                handleSubmit(e);
            }
        },
        [handleSubmit],
    );

    const handleEmojiClick = useCallback((emojiData) => {
        setMessage((prev) => prev + emojiData.emoji);
        inputRef.current?.focus();
    }, []);

    const handleAttachment = useCallback(() => {
        // Implement file attachment logic here
        console.log("Attachment button clicked");
    }, []);

    if (disabled) {
        return (
            <div className="p-3 border-top text-center text-white-50 bg-dark">
                <i className="bi bi-shield-exclamation me-2"></i>
                This conversation is blocked. You cannot send messages.
            </div>
        );
    }

    return (
        <Form onSubmit={handleSubmit} className="p-3">
            <InputGroup className="bg-dark rounded-pill border border-secondary p-1">
                {/* Attachment Button */}
                {/* <Button
                    variant="link"
                    className="text-white-50 p-2 border-0"
                    onClick={handleAttachment}
                    type="button"
                    title="Attach file"
                >
                    <i className="bi bi-paperclip fs-5"></i>
                </Button> */}

                {/* Emoji Button */}
                <div className="position-relative">
                    <Button
                        variant="link"
                        className={`text-white-50 py-1 border-0 ${showEmojiPicker ? "text-warning" : ""}`}
                        onClick={() => setShowEmojiPicker(!showEmojiPicker)}
                        type="button"
                        title="Add emoji"
                    >
                        <i className="bi bi-emoji-smile fs-5"></i>
                    </Button>

                    {/* Emoji Picker */}
                    {showEmojiPicker && (
                        <div
                            ref={emojiPickerRef}
                            className="position-absolute bottom-100 start-0 mb-2"
                            style={{ zIndex: 1000 }}
                        >
                            <EmojiPicker
                                onEmojiClick={handleEmojiClick}
                                autoFocusSearch={false}
                                theme="dark"
                                width={300}
                                height={400}
                            />
                        </div>
                    )}
                </div>

                {/* Message Input */}
                <Form.Control
                    ref={inputRef}
                    as="textarea"
                    value={message}
                    onChange={(e) => {
                        setMessage(e.target.value);
                        handleTyping();
                    }}
                    onKeyDown={handleKeyDown}
                    placeholder="Type a message..."
                    rows="1"
                    style={{
                        maxHeight: "120px",
                        resize: "none",
                        backgroundColor: "transparent",
                        border: "none",
                        color: "white",
                        outline: "none",
                        boxShadow: "none",
                    }}
                    className="bg-transparent text-white"
                />

                {/* Send Button */}
                <Button
                    variant="link"
                    className={`py-1 border-0 ${
                        message.trim() ? "text-warning" : "text-white-50"
                    }`}
                    type="submit"
                    disabled={!message.trim()}
                    title="Send message"
                >
                    <i
                        className={`bi ${message.trim() ? "bi-send-fill" : "bi-send"} fs-5`}
                    ></i>
                </Button>
            </InputGroup>
        </Form>
    );
}

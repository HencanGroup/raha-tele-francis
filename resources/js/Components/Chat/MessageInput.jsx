import React, { useState, useRef, useEffect, useCallback } from "react";
import { Form, Button, InputGroup } from "react-bootstrap";
import EmojiPicker from "emoji-picker-react";
import {
    typeFromMime,
    previewAttachment,
    ACCEPTED_ATTACHMENT_TYPES,
    MAX_ATTACHMENT_BYTES,
} from "@/Utils/chat";

export default function MessageInput({
    onSendMessage,
    onTyping,
    disabled = false,
    isSending = false,
}) {
    const [message, setMessage] = useState("");
    const [attachment, setAttachment] = useState(null);
    const [error, setError] = useState(null);
    const [isTyping, setIsTyping] = useState(false);
    const [showEmojiPicker, setShowEmojiPicker] = useState(false);
    const typingTimeoutRef = useRef(null);
    const inputRef = useRef(null);
    const fileInputRef = useRef(null);
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

            if (disabled || isSending) return;

            const text = message.trim();
            if (!text && !attachment) return;

            const type = attachment ? typeFromMime(attachment.type) : "text";

            // Send text, type, replyTo, and the optional file (multipart).
            onSendMessage(text, type, null, attachment);

            setMessage("");
            setAttachment(null);
            if (fileInputRef.current) fileInputRef.current.value = "";

            // Reset typing indicator
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
            }
            setIsTyping(false);
            onTyping(false);
        },
        [message, attachment, disabled, isSending, onSendMessage, onTyping],
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

    // Open the hidden file picker.
    const handleAttachment = useCallback(() => {
        fileInputRef.current?.click();
    }, []);

    // Validate and stage the chosen file — the backend caps at 10MB and
    // accepts jpg/png/gif/webp/mp4/mp3/ogg/pdf/doc/docx.
    const handleFileChange = useCallback((e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (file.size > MAX_ATTACHMENT_BYTES) {
            setError("Attachment must be under 10MB.");
            return;
        }

        setAttachment(file);
        setError(null);
        inputRef.current?.focus();
    }, []);

    const handleRemoveAttachment = useCallback(() => {
        setAttachment(null);
        if (fileInputRef.current) fileInputRef.current.value = "";
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
                {/* Attachment Button — posts via multipart to /api/chat/messages */}
                <Button
                    variant="link"
                    className="text-white-50 py-1 border-0"
                    onClick={handleAttachment}
                    type="button"
                    title="Attach file"
                    disabled={isSending}
                >
                    <i className="bi bi-paperclip fs-5"></i>
                </Button>

                {/* Hidden file input */}
                <input
                    ref={fileInputRef}
                    type="file"
                    accept={ACCEPTED_ATTACHMENT_TYPES}
                    onChange={handleFileChange}
                    style={{ display: "none" }}
                />

                {/* Emoji Button */}
                <div className="position-relative">
                    <Button
                        variant="link"
                        className={`text-white-50 py-1 border-0 ${showEmojiPicker ? "text-warning" : ""}`}
                        onClick={() => setShowEmojiPicker(!showEmojiPicker)}
                        type="button"
                        title="Add emoji"
                        disabled={isSending}
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
                    placeholder={
                        attachment ? "Add a caption…" : "Type a message..."
                    }
                    rows="1"
                    disabled={isSending}
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
                        message.trim() || attachment
                            ? "text-warning"
                            : "text-white-50"
                    }`}
                    type="submit"
                    disabled={(!message.trim() && !attachment) || isSending}
                    title="Send message"
                >
                    <i
                        className={`bi ${message.trim() || attachment ? "bi-send-fill" : "bi-send"} fs-5`}
                    ></i>
                </Button>
            </InputGroup>

            {/* Staged attachment preview */}
            {attachment && (
                <div className="d-flex align-items-center gap-2 mt-2 px-2">
                    <i className="bi bi-paperclip text-white-50"></i>
                    <span className="small text-white text-truncate">
                        {attachment.name}
                    </span>
                    <span className="small text-white-50 flex-shrink-0">
                        {(attachment.size / 1024).toFixed(1)} KB
                    </span>
                    <Button
                        variant="link"
                        className="p-0 ms-auto border-0 text-white-50"
                        onClick={handleRemoveAttachment}
                        title="Remove attachment"
                    >
                        <i className="bi bi-x-circle"></i>
                    </Button>
                </div>
            )}

            {error && (
                <div className="small text-danger mt-1 px-2">{error}</div>
            )}
        </Form>
    );
}
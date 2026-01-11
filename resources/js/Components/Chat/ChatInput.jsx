import React, { useState, useRef, useEffect, memo, useMemo } from "react";
import { Button, Form, Spinner, Alert, InputGroup } from "react-bootstrap";
import {
    Smile,
    Paperclip,
    Send,
    File,
    X,
    Coins,
    AlertCircle,
} from "lucide-react";
import Picker from "@emoji-mart/react";
import data from "@emoji-mart/data";
import PropTypes from "prop-types";
import { toast } from "react-toastify";
import { formatFileSize } from "@/Utils/helpers.jsx";

/* -------------------- CONSTANTS -------------------- */
const MAX_MESSAGE_LENGTH = 160;
const CREDITS_PER_MESSAGE = 1;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

/* -------------------- HELPERS -------------------- */
const getFileType = (file) => {
    if (!file?.type) return "file";
    if (file.type.startsWith("image/")) return "image";
    if (file.type.startsWith("video/")) return "video";
    if (file.type.startsWith("audio/")) return "audio";
    return "file";
};

/* -------------------- FILE PREVIEW -------------------- */
const FilePreview = memo(({ file, onRemove }) => {
    const previewUrl = useMemo(() => {
        if (
            file.type.startsWith("image/") ||
            file.type.startsWith("video/") ||
            file.type.startsWith("audio/")
        ) {
            return URL.createObjectURL(file);
        }
        return null;
    }, [file]);

    useEffect(() => {
        return () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
        };
    }, [previewUrl]);

    return (
        <div className="bg-dark rounded p-2 mb-2">
            {file.type.startsWith("image/") && (
                <img
                    src={previewUrl}
                    alt={file.name}
                    className="img-fluid rounded mb-2"
                    style={{ maxHeight: 200 }}
                />
            )}

            {file.type.startsWith("video/") && (
                <video
                    src={previewUrl}
                    controls
                    className="w-100 rounded mb-2"
                    style={{ maxHeight: 240 }}
                />
            )}

            {file.type.startsWith("audio/") && (
                <audio src={previewUrl} controls className="w-100 mb-2" />
            )}

            <div className="d-flex align-items-center">
                <File size={16} className="me-2 text-primary" />
                <div className="flex-grow-1">
                    <small className="text-white d-block text-truncate">
                        {file.name}
                    </small>
                    <small className="text-white-50">
                        {formatFileSize(file.size)}
                    </small>
                </div>
                <Button
                    variant="link"
                    className="text-white-50"
                    onClick={onRemove}
                >
                    <X size={16} />
                </Button>
            </div>
        </div>
    );
});

/* -------------------- EMOJI PICKER -------------------- */
const EmojiPicker = memo(({ onSelect }) => (
    <div
        className="position-absolute bottom-100 start-0 mb-1"
        style={{ zIndex: 2000 }}
    >
        <Picker
            data={data}
            onEmojiSelect={onSelect}
            theme="dark"
            previewPosition="none"
            skinTonePosition="none"
        />
    </div>
));

/* -------------------- MAIN COMPONENT -------------------- */
const ChatInput = ({
    newMessage,
    setNewMessage,
    handleSendMessage,
    isSending,
    userCredits,
    onBuyCoins,
}) => {
    const [selectedFile, setSelectedFile] = useState(null);
    const [showEmojiPicker, setShowEmojiPicker] = useState(false);
    const [showLimitAlert, setShowLimitAlert] = useState(false);

    const inputRef = useRef(null);
    const fileInputRef = useRef(null);
    const containerRef = useRef(null);
    const focusCheckTimerRef = useRef(null);

    const characterCount = newMessage.length;
    const maxChars =
        Math.floor(userCredits / CREDITS_PER_MESSAGE) * MAX_MESSAGE_LENGTH;
    const messageCount = Math.ceil(characterCount / MAX_MESSAGE_LENGTH);
    const totalCost = messageCount * CREDITS_PER_MESSAGE;

    /* -------------------- SIMPLE FOCUS CHECK -------------------- */
    const checkAndFocusInput = () => {
        const activeElement = document.activeElement;
        const isOtherInputFocused =
            activeElement &&
            activeElement !== document.body &&
            activeElement !== inputRef.current &&
            activeElement !== fileInputRef.current &&
            (activeElement.tagName === "INPUT" ||
                activeElement.tagName === "TEXTAREA" ||
                activeElement.tagName === "SELECT" ||
                activeElement.isContentEditable);

        // If no other input is focused, focus our message input
        if (!isOtherInputFocused && inputRef.current) {
            inputRef.current.focus();
        }
    };

    /* -------------------- EFFECTS -------------------- */
    useEffect(() => {
        // Focus on initial mount
        if (inputRef.current) {
            inputRef.current.focus();
        }

        // Set up periodic focus check (every 500ms)
        focusCheckTimerRef.current = setInterval(checkAndFocusInput, 500);

        return () => {
            if (focusCheckTimerRef.current) {
                clearInterval(focusCheckTimerRef.current);
            }
        };
    }, []);

    useEffect(() => {
        // Focus after sending or removing file
        const timer = setTimeout(() => {
            checkAndFocusInput();
        }, 50);

        return () => clearTimeout(timer);
    }, [isSending, selectedFile]);

    useEffect(() => {
        // Focus when emoji picker closes
        if (!showEmojiPicker) {
            const timer = setTimeout(() => {
                checkAndFocusInput();
            }, 50);

            return () => clearTimeout(timer);
        }
    }, [showEmojiPicker]);

    useEffect(() => {
        // Close emoji picker when clicking outside
        const handleClickOutside = (e) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(e.target)
            ) {
                setShowEmojiPicker(false);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);
        return () => {
            document.removeEventListener("mousedown", handleClickOutside);
        };
    }, []);

    /* -------------------- HANDLERS -------------------- */
    const handleInputChange = (e) => {
        if (e.target.value.length > maxChars) {
            setShowLimitAlert(true);
            setNewMessage(e.target.value.slice(0, maxChars));
            return;
        }
        setShowLimitAlert(false);
        setNewMessage(e.target.value);
    };

    const handleKeyDown = (e) => {
        if (e.key === "Escape") {
            setShowEmojiPicker(false);
            return;
        }

        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            handleSend(e);
            return;
        }

        if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
            e.preventDefault();
            handleSend(e);
            return;
        }

        if (e.key === "Backspace" && !newMessage.length && selectedFile) {
            setSelectedFile(null);
        }
    };

    const addEmoji = (emoji) => {
        setNewMessage((prev) => prev + emoji.native);
        // Focus input after adding emoji
        setTimeout(() => {
            if (inputRef.current) {
                inputRef.current.focus();
            }
        }, 10);
    };

    const handleFileSelect = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (file.size > MAX_FILE_SIZE) {
            toast.error("File too large (max 10MB)");
            return;
        }

        setSelectedFile(file);
        e.target.value = "";

        // Focus input after file selection
        setTimeout(() => {
            if (inputRef.current) {
                inputRef.current.focus();
            }
        }, 50);
    };

    const handleSend = async (e) => {
        e.preventDefault();

        if (!newMessage.trim() && !selectedFile) {
            toast.info("Type a message or attach a file");
            setTimeout(() => {
                if (inputRef.current) {
                    inputRef.current.focus();
                }
            }, 50);
            return;
        }

        if (userCredits < totalCost) {
            toast.warning("Not enough credits");
            setTimeout(() => {
                if (inputRef.current) {
                    inputRef.current.focus();
                }
            }, 50);
            return;
        }

        await handleSendMessage({
            message: newMessage,
            attachment: selectedFile,
            type: selectedFile ? getFileType(selectedFile) : "text",
            requires_credit: true,
            credit_cost: totalCost,
        });

        setNewMessage("");
        setSelectedFile(null);

        // Focus after sending
        setTimeout(() => {
            if (inputRef.current) {
                inputRef.current.focus();
            }
        }, 100);
    };

    /* -------------------- NO CREDITS -------------------- */
    if (userCredits < 1) {
        return (
            <div className="position-relative p-2">
                <Alert
                    variant="warning"
                    className="d-flex align-items-center gap-1 py-1 mb-1 rounded-pill"
                >
                    <AlertCircle size={18} />
                    <span>You need coins to chat</span>
                    <Button
                        size="sm"
                        variant="warning"
                        onClick={onBuyCoins}
                        className="rounded-pill"
                    >
                        <Coins size={14} className="me-2" />
                        Load Coins
                    </Button>
                </Alert>
            </div>
        );
    }

    /* -------------------- RENDER -------------------- */
    return (
        <div ref={containerRef} className="position-relative p-2">
            {showLimitAlert && (
                <Alert
                    variant="danger"
                    className="d-flex align-items-center gap-1 py-1 mb-1 rounded-pill"
                >
                    <AlertCircle size={18} />
                    <span>Message too long — buy more coins</span>
                </Alert>
            )}

            {selectedFile && (
                <FilePreview
                    file={selectedFile}
                    onRemove={() => setSelectedFile(null)}
                />
            )}

            <InputGroup className="bg-dark rounded-pill align-items-end p-1 gap-1">
                <Button
                    variant="dark"
                    className="text-white-50 rounded-circle px-2"
                    onClick={() => {
                        setShowEmojiPicker((v) => !v);
                    }}
                >
                    <Smile size={20} />
                </Button>

                <Button
                    variant="dark"
                    className="text-white-50 rounded-circle px-2"
                    onClick={() => {
                        fileInputRef.current.click();
                    }}
                >
                    <Paperclip size={20} />
                </Button>

                <input
                    type="file"
                    hidden
                    ref={fileInputRef}
                    onChange={handleFileSelect}
                />

                <Form.Control
                    as="textarea"
                    ref={inputRef}
                    rows={1}
                    value={newMessage}
                    onChange={handleInputChange}
                    onKeyDown={handleKeyDown}
                    placeholder="Type a message..."
                    className="bg-transparent border-0 flex-grow-1 text-white"
                    style={{ resize: "none" }}
                />

                {!isSending ? (
                    <Button
                        variant="warning"
                        className="rounded-circle px-2"
                        onClick={handleSend}
                    >
                        <Send size={20} />
                    </Button>
                ) : (
                    <Spinner size="sm" />
                )}
            </InputGroup>

            {showEmojiPicker && <EmojiPicker onSelect={addEmoji} />}
        </div>
    );
};

/* -------------------- PROPS -------------------- */
ChatInput.propTypes = {
    newMessage: PropTypes.string.isRequired,
    setNewMessage: PropTypes.func.isRequired,
    handleSendMessage: PropTypes.func.isRequired,
    isSending: PropTypes.bool,
    userCredits: PropTypes.number.isRequired,
    onBuyCoins: PropTypes.func,
};

export default memo(ChatInput);

// resources/js/Utils/chat.jsx
// Shared helpers for the chat UI: normalising message shapes from the session
// props, the Sanctum API, and the broadcast payloads into one canonical shape
// the chat components render, plus the API calls for sending, history, and
// reactions. All /api/chat/* calls go through xios so the Bearer token and
// CSRF headers are attached (see Utils/xios.jsx).

import xios from "@/Utils/xios";
import { ensureSessionToken } from "@/Utils/auth";

// Preset emojis offered in the message reaction row.
export const REACTION_EMOJIS = ["❤️", "👍", "😂", "🔥", "😮"];

// Max attachment size accepted by the backend (SendMessageRequest: 10MB).
export const MAX_ATTACHMENT_BYTES = 10 * 1024 * 1024;

// Accepted attachment extensions (mirrors the backend mimes rule).
export const ACCEPTED_ATTACHMENT_TYPES =
    "image/jpeg,image/png,image/gif,image/webp,video/mp4,audio/mpeg,audio/ogg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document";

// Pick a compact user object for a message sender.
const pickUser = (user) =>
    user
        ? {
              id: user.id,
              name: user.name,
              display_name: user.display_name,
              profile_photo_url: user.profile_photo_url,
              is_online: user.is_online,
              last_seen: user.last_seen,
          }
        : null;

/**
 * Normalise a message into the canonical shape the chat components render.
 *
 * Handles three input shapes:
 *   - Session props (has `sender` object + `is_mine`)
 *   - API responses (has `sender_id`, `receiver_id`, `requires_credit`, …)
 *   - Broadcast payloads (has `sender` object + the credit fields added in
 *     NewMessage::broadcastWith)
 *
 * Locked messages are flagged for the receiver only — the sender always sees
 * their own content.
 */
export const normalizeMessage = (m, { authUser, otherUser }) => {
    if (!m) return null;

    const senderId = m.sender?.id ?? m.sender_id;
    const isMine = m.is_mine ?? senderId === authUser?.id;
    const isLocked =
        !!m.is_locked ||
        (!!m.requires_credit && !m.is_paid && !isMine);

    return {
        id: m.id,
        conversation_id: m.conversation_id,
        message: m.message ?? "",
        type: m.type ?? "text",
        sender:
            m.sender ??
            pickUser(senderId === authUser?.id ? authUser : otherUser),
        receiver_id: m.receiver_id,
        client_id: m.client_id,
        is_sent: m.is_sent ?? true,
        is_delivered: !!m.is_delivered,
        is_read: !!m.is_read,
        is_mine: isMine,
        is_locked: isLocked,
        requires_credit: !!m.requires_credit,
        credit_cost: Number(m.credit_cost ?? 0),
        is_paid: !!m.is_paid,
        payment_verified: !!m.payment_verified,
        reactions: m.reactions ?? {},
        attachments: m.attachments ?? null,
        reply_to_id: m.reply_to_id ?? null,
        created_at: m.created_at ?? m.sent_at ?? new Date().toISOString(),
    };
};

/**
 * Merge messages from different sources (server props, API pages, broadcasts)
 * by id, then sort oldest→newest for display.
 */
export const mergeMessages = (existing, incoming) => {
    const map = new Map();

    [...(existing ?? []), ...(incoming ?? [])].forEach((m) => {
        if (m && m.id) map.set(String(m.id), m);
    });

    return [...map.values()].sort(
        (a, b) => new Date(a.created_at) - new Date(b.created_at),
    );
};

// Derive the chat message type from a file's MIME type.
export const typeFromMime = (mime) => {
    if (!mime) return "file";
    if (mime.startsWith("image/")) return "image";
    if (mime.startsWith("video/")) return "video";
    if (mime.startsWith("audio/")) return "audio";
    return "file";
};

// Build a local object URL for an optimistic attachment preview.
export const previewAttachment = (file) => ({
    path: URL.createObjectURL(file),
    name: file.name,
    size: file.size,
    mime: file.type,
});

/**
 * POST /api/chat/messages — sends a message with an optional multipart
 * attachment. Ensures a Sanctum token for the current user exists first so
 * auth:sanctum passes (and a stale token from another account is re-minted).
 */
export const sendApiMessage = async (payload, attachment = null, userId) => {
    await ensureSessionToken(userId);

    if (attachment) {
        const form = new FormData();
        form.append("conversation_id", payload.conversation_id);
        if (payload.message) form.append("message", payload.message);
        if (payload.type) form.append("type", payload.type);
        if (payload.client_id) form.append("client_id", payload.client_id);
        if (payload.reply_to_id) form.append("reply_to_id", payload.reply_to_id);
        form.append("attachment", attachment);

        return xios.post("/api/chat/messages", form, {
            headers: { "Content-Type": "multipart/form-data" },
        });
    }

    return xios.post("/api/chat/messages", payload);
};

/**
 * GET /api/chat/conversations/{conversation}/messages — paginated history,
 * newest first (50 per page). Returns the raw { data, meta } envelope.
 */
export const fetchHistory = async (conversationId, page = 1, userId) => {
    await ensureSessionToken(userId);

    const { data } = await xios.get(
        `/api/chat/conversations/${conversationId}/messages`,
        { params: { page, per_page: 50 } },
    );

    return data;
};

/**
 * POST /api/chat/messages/{message}/unlock — the member pays credits to
 * reveal a locked message; returns the unlocked message in the `data` key.
 */
export const unlockMessage = async (messageId, userId) => {
    await ensureSessionToken(userId);
    const { data } = await xios.post(`/api/chat/messages/${messageId}/unlock`);
    return data.data;
};

/**
 * Add/overwrite the current user's reaction on a message.
 */
export const addReaction = async (messageId, reaction, userId) => {
    await ensureSessionToken(userId);
    const { data } = await xios.post(
        `/api/chat/messages/${messageId}/reactions`,
        { reaction },
    );
    return data.data;
};

/**
 * Remove the current user's reaction from a message (idempotent, 204).
 */
export const removeReaction = async (messageId, userId) => {
    await ensureSessionToken(userId);
    await xios.delete(`/api/chat/messages/${messageId}/reactions`);
};
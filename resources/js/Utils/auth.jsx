// resources/js/Utils/auth.jsx
import { router } from "@inertiajs/react";
import xios, { SANCTUM_TOKEN_KEY } from "@/Utils/xios";

// Storage key recording which user the stored token belongs to. The token
// lives in localStorage (shared across tabs/sessions on the same origin), so
// we must verify the token owner against the current session user before
// reusing it (see ensureSessionToken) — otherwise a stale token from another
// account would authenticate every /api/* call as the wrong user.
const SANCTUM_TOKEN_USER_KEY = "raha_sanctum_token_user";

// Session-storage key for the temporary 2FA login token.
const TWO_FACTOR_TOKEN_KEY = "raha_2fa_token";

/**
 * Read the stored Sanctum Bearer token (or null).
 */
export const getToken = () => window.localStorage?.getItem(SANCTUM_TOKEN_KEY);

/**
 * Read the id of the user the stored token belongs to (or null).
 */
const getTokenUserId = () => window.localStorage?.getItem(SANCTUM_TOKEN_USER_KEY);

/**
 * Ensure a Sanctum Bearer token for the CURRENT session user exists.
 *
 * The stored token is only reused when it belongs to the same user as the
 * session (expectedUserId). Otherwise a fresh token is minted from the
 * session via POST /auth/issue-token (which always mints for the session
 * user) and persisted alongside its owner id. This prevents a stale token
 * left over from a previous account in the same browser from being used.
 */
export const ensureSessionToken = async (expectedUserId) => {
    const existing = getToken();

    if (
        existing &&
        expectedUserId != null &&
        String(getTokenUserId()) === String(expectedUserId)
    ) {
        return existing;
    }

    const { data } = await xios.post("/auth/issue-token");
    setToken(data.token);
    window.localStorage?.setItem(SANCTUM_TOKEN_USER_KEY, data.user_id);
    return data.token;
};

/**
 * Persist the Sanctum Bearer token.
 */
export const setToken = (token) =>
    window.localStorage?.setItem(SANCTUM_TOKEN_KEY, token);

/**
 * Remove the stored Sanctum Bearer token and its owner id.
 */
export const clearToken = () => {
    window.localStorage?.removeItem(SANCTUM_TOKEN_KEY);
    window.localStorage?.removeItem(SANCTUM_TOKEN_USER_KEY);
};

/**
 * POST /api/auth/login — returns { token, user } or
 * { two_factor_required: true, two_factor_token }.
 */
export const login = async ({ email, password }) => {
    const { data } = await xios.post("/api/auth/login", { email, password });
    return data;
};

/**
 * POST /api/auth/2fa/verify — exchange the temporary token + TOTP code
 * for a full Sanctum token.
 */
export const verify2fa = async ({ code, twoFactorToken }) => {
    const { data } = await xios.post("/api/auth/2fa/verify", {
        code,
        two_factor_token: twoFactorToken,
    });
    return data;
};

/**
 * POST /api/auth/2fa/recovery — exchange the temporary token + recovery
 * code for a full Sanctum token.
 */
export const recovery2fa = async ({ recoveryCode, twoFactorToken }) => {
    const { data } = await xios.post("/api/auth/2fa/recovery", {
        recovery_code: recoveryCode,
        two_factor_token: twoFactorToken,
    });
    return data;
};

/**
 * POST /api/auth/logout — revoke the current Sanctum token, then clear it.
 */
export const apiLogout = async () => {
    try {
        await xios.post("/api/auth/logout");
    } finally {
        clearToken();
    }
};

/**
 * POST /auth/bridge — swap the Sanctum token into the web session so the
 * session-guarded Inertia routes and usePage().props.auth keep working.
 */
export const bridgeSession = async (token) => {
    await xios.post(
        "/auth/bridge",
        {},
        { headers: { Authorization: `Bearer ${token}` } }
    );
};

/**
 * Finish an API login: persist the token, establish the web session, and
 * navigate to the authenticated home page.
 */
export const completeAuth = async ({ token, user }, redirectTo = "/dashboard") => {
    setToken(token);
    // Remember which user this token belongs to so ensureSessionToken can
    // detect a stale token from another account in the same browser.
    if (user?.id) {
        window.localStorage?.setItem(SANCTUM_TOKEN_USER_KEY, user.id);
    }
    await bridgeSession(token);
    router.visit(redirectTo);
};

/**
 * GET /api/auth/2fa/status — whether 2FA is enabled for the user.
 */
export const get2faStatus = async () => {
    const { data } = await xios.get("/api/auth/2fa/status");
    return data;
};

/**
 * POST /api/auth/2fa/enable — begins setup; returns secret, QR URL and
 * recovery codes (2FA is not active until confirmed with a TOTP code).
 */
export const enable2fa = async (password) => {
    const { data } = await xios.post("/api/auth/2fa/enable", { password });
    return data;
};

/**
 * POST /api/auth/2fa/confirm — activate 2FA with a 6-digit TOTP code.
 */
export const confirm2fa = async (code) => {
    const { data } = await xios.post("/api/auth/2fa/confirm", { code });
    return data;
};

/**
 * POST /api/auth/2fa/disable — turn 2FA off (requires password + TOTP code).
 */
export const disable2fa = async ({ password, code }) => {
    const { data } = await xios.post("/api/auth/2fa/disable", { password, code });
    return data;
};

/**
 * Store the temporary 2FA login token (used between login and the challenge).
 */
export const storeTwoFactorToken = (token) =>
    window.sessionStorage?.setItem(TWO_FACTOR_TOKEN_KEY, token);

/**
 * Read and clear the temporary 2FA login token.
 */
export const takeTwoFactorToken = () => {
    const token = window.sessionStorage?.getItem(TWO_FACTOR_TOKEN_KEY);
    window.sessionStorage?.removeItem(TWO_FACTOR_TOKEN_KEY);
    return token;
};

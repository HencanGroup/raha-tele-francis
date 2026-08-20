// resources/js/utils/xios.jsx
import axios from "axios";
import { router } from "@inertiajs/react";

// Storage key for the Sanctum Bearer token issued by /api/auth/login
// (shared with Utils/auth.jsx — keep in sync).
export const SANCTUM_TOKEN_KEY = "raha_sanctum_token";

// Storage key recording which user the stored token belongs to (also shared
// with Utils/auth.jsx — keep in sync).
export const SANCTUM_TOKEN_USER_KEY = "raha_sanctum_token_user";

// Guards against duplicate auto-logout redirects when several authed API
// calls 401 at once (e.g. token expiry).
let authRedirecting = false;

// Create an Axios instance
const xios = axios.create({
    headers: {
        "X-Requested-With": "XMLHttpRequest",
    },
});

// Set dynamic headers (e.g., from window object)
xios.interceptors.request.use((config) => {
    const deviceId = window.deviceId || "UNKNOWN_DEVICE";
    const latitude = window.latitude || "0";
    const longitude = window.longitude || "0";
    const accuracy = window.locationAccuracy || "0";
    const locationSource = window.locationSource || "unknown";

    config.headers["X-Device-ID"] = deviceId;
    config.headers["X-Latitude"] = latitude;
    config.headers["X-Longitude"] = longitude;
    config.headers["X-Location-Accuracy"] = accuracy;
    config.headers["X-Location-Source"] = locationSource;

    // Attach the Sanctum Bearer token so /api/* (auth:sanctum) calls work.
    const token = window.localStorage?.getItem(SANCTUM_TOKEN_KEY);
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    // Attach the CSRF token for session routes (e.g. /auth/bridge).
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    if (csrfToken) {
        config.headers["X-CSRF-TOKEN"] = csrfToken;
    }

    return config;
});

// Auto-logout on token expiry: a 401 from an *authed* /api/* call means the
// Sanctum token is invalid or expired. Clear it and end the web session so
// the user is signed out and a fresh token is minted on the next login.
xios.interceptors.response.use(
    (response) => response,
    (error) => {
        const { response, config } = error ?? {};
        const url = config?.url || "";
        const status = response?.status;
        const wasAuthed = Boolean(config?.headers?.Authorization);

        if (
            status === 401 &&
            wasAuthed &&
            url.startsWith("/api/") &&
            !url.includes("/auth/login") &&
            !url.includes("/auth/logout") &&
            !url.includes("/auth/2fa")
        ) {
            // Drop the stale token + its owner id so ensureSessionToken mints
            // a fresh one for the current session user.
            window.localStorage?.removeItem(SANCTUM_TOKEN_KEY);
            window.localStorage?.removeItem(SANCTUM_TOKEN_USER_KEY);

            if (!authRedirecting) {
                authRedirecting = true;
                // End the web session too — "auto logout" on token expiry.
                router.post(
                    route("logout"),
                    {},
                    {
                        onFinish: () => {
                            authRedirecting = false;
                        },
                    },
                );
            }
        }

        return Promise.reject(error);
    },
);

export default xios;

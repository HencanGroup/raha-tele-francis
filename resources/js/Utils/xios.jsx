// resources/js/utils/xios.jsx
import axios from "axios";

// Storage key for the Sanctum Bearer token issued by /api/auth/login
// (shared with Utils/auth.js — keep in sync).
export const SANCTUM_TOKEN_KEY = "raha_sanctum_token";

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

export default xios;

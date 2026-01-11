// resources/js/utils/axios.js
import axios from "axios";

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

    return config;
});

export default xios;

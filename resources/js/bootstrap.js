import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Import echo with error handling
import "./echo";

// Add global error handler for unhandled rejections
window.addEventListener("unhandledrejection", function (event) {
    console.warn("Unhandled Promise Rejection:", event.reason);
    // Don't throw, just log in development
    if (import.meta.env.PROD) {
        // Optionally send to error tracking service
        event.preventDefault();
    }
});

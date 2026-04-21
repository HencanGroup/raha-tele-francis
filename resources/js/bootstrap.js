import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Add CSRF token to axios default headers
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    console.log('CSRF token configured:', token.substring(0, 10) + '...');
} else {
    console.warn('CSRF token not found!');
}

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

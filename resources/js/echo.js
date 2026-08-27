import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Make Pusher available globally
window.Pusher = Pusher;

// Initialize Echo with proper error handling
try {
    window.Echo = new Echo({
        broadcaster: "pusher",
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,
        disableStats: true,
        activityTimeout: 30000,
        pongTimeout: 30000,
    });

    console.log("✅ Echo initialized successfully");
} catch (error) {
    console.error("❌ Failed to initialize Echo:", error);
}

// Pusher connection status logs — always visible (not DEV-only) so
// production connection issues can be diagnosed from browser console.
if (window.Echo) {
    setTimeout(() => {
        try {
            if (window.Echo.connector && window.Echo.connector.pusher) {
                window.Echo.connector.pusher.connection.bind(
                    "connected",
                    () => {
                        console.log("✅ Connected to Pusher successfully");
                    },
                );

                window.Echo.connector.pusher.connection.bind(
                    "disconnected",
                    () => {
                        console.log("❌ Disconnected from Pusher");
                    },
                );

                window.Echo.connector.pusher.connection.bind(
                    "error",
                    (error) => {
                        console.error("🔴 Pusher connection error:", error);
                    },
                );

                window.Echo.connector.pusher.connection.bind(
                    "state_change",
                    (states) => {
                        console.log("🔄 Connection state changed:", states);
                    },
                );
            } else {
                console.warn("⚠️ Echo connector not ready yet");
            }
        } catch (error) {
            console.error("❌ Error setting up Echo event listeners:", error);
        }
    }, 1000);
}

// Export for use in other modules
export default window.Echo;

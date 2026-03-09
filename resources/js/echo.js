import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Make Pusher available globally
window.Pusher = Pusher;

// Initialize Echo with proper error handling
try {
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "http") === "https",
        enabledTransports: ["ws", "wss"],
        disableStats: true,
        // Add connection timeout
        activityTimeout: 30000,
        pongTimeout: 30000,
    });

    console.log("✅ Echo initialized successfully");
} catch (error) {
    console.error("❌ Failed to initialize Echo:", error);
}

// Safely check connection status with proper error handling
if (import.meta.env.DEV && window.Echo) {
    // Wait for connection to be established before binding events
    setTimeout(() => {
        try {
            if (window.Echo.connector && window.Echo.connector.pusher) {
                window.Echo.connector.pusher.connection.bind(
                    "connected",
                    () => {
                        console.log("✅ Connected to Reverb successfully");
                    },
                );

                window.Echo.connector.pusher.connection.bind(
                    "disconnected",
                    () => {
                        console.log("❌ Disconnected from Reverb");
                    },
                );

                window.Echo.connector.pusher.connection.bind(
                    "error",
                    (error) => {
                        console.error("🔴 Reverb connection error:", error);
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
    }, 1000); // Delay to allow connection to initialize
}

// Export for use in other modules
export default window.Echo;

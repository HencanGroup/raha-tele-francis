import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

try {
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: parseInt(import.meta.env.VITE_REVERB_PORT ?? "8080"),
        wssPort: parseInt(import.meta.env.VITE_REVERB_PORT ?? "443"),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "http") === "https",
        enabledTransports: ["ws", "wss"],
        disableStats: true,
        activityTimeout: 30000,
        pongTimeout: 30000,
        authEndpoint: "/broadcasting/auth",
        auth: {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
        },
    });

    console.log("Echo initialized successfully (Reverb)");
} catch (error) {
    console.error("Failed to initialize Echo:", error);
}

if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
    window.Echo.connector.pusher.connection.bind("connected", () => {
        console.log("Connected to Reverb successfully");
    });

    window.Echo.connector.pusher.connection.bind("disconnected", () => {
        console.log("Disconnected from Reverb");
    });

    window.Echo.connector.pusher.connection.bind("error", (error) => {
        console.error("Reverb connection error:", error);
    });

    window.Echo.connector.pusher.connection.bind("state_change", (states) => {
        console.log("Connection state changed:", states);
    });
} else {
    console.warn("Echo connector not ready yet");
}

export default window.Echo;

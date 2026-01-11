import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

export const echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_REVERB_APP_KEY || "local",
    wsHost: import.meta.env.VITE_REVERB_HOST || "127.0.0.1",
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false,
    encrypted: false,
    disableStats: true,
    cluster: "mt1",
    enabledTransports: ["ws", "wss"],
});

// useOnlineStatus.js
import { useState, useEffect } from "react";
import axios from "axios";

export default function useOnlineStatus() {
    const [isOnline, setIsOnline] = useState(navigator.onLine);

    useEffect(() => {
        const updateStatus = async (online) => {
            setIsOnline(online);

            if (online) {
                try {
                    await axios.get(route("heartbeat"));
                } catch (err) {
                    console.error("Heartbeat failed:", err);
                }
            }
        };

        const handleOnline = () => updateStatus(true);
        const handleOffline = () => updateStatus(false);

        window.addEventListener("online", handleOnline);
        window.addEventListener("offline", handleOffline);

        // Optional: send periodic heartbeat even if user is online
        const interval = setInterval(() => {
            if (navigator.onLine) updateStatus(true);
        }, 15000); // every 15s

        return () => {
            window.removeEventListener("online", handleOnline);
            window.removeEventListener("offline", handleOffline);
            clearInterval(interval);
        };
    }, []);

    return isOnline;
}

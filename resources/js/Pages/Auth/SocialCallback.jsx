import React, { useEffect, useRef } from "react";
import { router } from "@inertiajs/react";
import { setToken, bridgeSession } from "@/Utils/auth";

// Landing page for the OAuth callback. The server redirects here with a
// Sanctum token (?token=...) after a successful Google/Facebook login; we
// persist it, establish the web session, and continue to the dashboard.
export default function SocialCallback() {
    const handled = useRef(false);

    useEffect(() => {
        if (handled.current) return;
        handled.current = true;

        const token = new URLSearchParams(window.location.search).get("token");

        if (!token) {
            router.visit(route("login"));
            return;
        }

        setToken(token);
        bridgeSession(token)
            .then(() => router.visit("/dashboard"))
            .catch(() => router.visit(route("login")));
    }, []);

    return (
        <div className="d-flex flex-column justify-content-center align-items-center vh-100">
            <div
                className="spinner-border text-gold mb-3"
                role="status"
            ></div>
            <p className="text-white-50 mb-0">Completing sign in...</p>
        </div>
    );
}
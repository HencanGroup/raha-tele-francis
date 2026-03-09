/******************************************************
 * 🧩 GLOBAL STYLES & VENDOR IMPORTS
 ******************************************************/
import "./bootstrap";
// import "./echo";

import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "bootstrap-icons/font/bootstrap-icons.css";

import jquery from "jquery";
import "jquery-validation";
import "jquery-validation/dist/additional-methods";

window.$ = window.jQuery = jquery;

import "datatables.net-bs5";
import "datatables.net-bs5/css/dataTables.bootstrap5.min.css";

import "../css/app.css";

/******************************************************
 * ⚛️ REACT & INERTIA IMPORTS
 ******************************************************/
import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createRoot } from "react-dom/client";
import { ChatProvider } from "./Components/Contexts/ChatContext";

/******************************************************
 * 🎨 THEME INITIALIZATION
 ******************************************************/
const initializeTheme = () => {
    const savedTheme = localStorage.getItem("lightMode") === "true";
    document.body.classList.toggle("light-mode", savedTheme);
};

// Initialize theme immediately when this file loads
initializeTheme();

/******************************************************
 * 🚀 APP CONFIGURATION
 ******************************************************/
const appName = import.meta.env.VITE_APP_NAME || "Raha Tele";

/******************************************************
 * 📱 INERTIA APP CREATION
 ******************************************************/
createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob("./Pages/**/*.jsx"),
        ),

    setup({ el, App, props }) {
        const root = createRoot(el);

        // Get authenticated user from props
        const { auth } = props.initialPage.props;

        root.render(
            <ChatProvider auth={auth}>
                <App {...props} />
            </ChatProvider>,
        );
    },

    progress: {
        color: "#ffbf00",
    },
});

/******************************************************
 * 📡 GLOBAL ECHO CONFIGURATION (Optional)
 ******************************************************/
// Make Echo available globally for debugging if needed
if (import.meta.env.DEV) {
    window.Echo = Echo;
}

/******************************************************
 * 🛡️ ERROR HANDLING (Optional)
 ******************************************************/
window.addEventListener("unhandledrejection", function (event) {
    console.error("Unhandled Promise Rejection:", event.reason);

    // You can add custom error reporting here
    if (import.meta.env.PROD) {
        // Send to your error tracking service
        // Sentry.captureException(event.reason);
    }
});

import Footer from "@/Components/Partials/Footer";
import HeaderLinks from "@/Components/Partials/HeaderLinks";
import NavBar from "@/Components/Partials/NavBar";
import { ToastContainer } from "react-toastify";

// Import the custom hook
import useOnlineStatus from "@/Hooks/useOnlineStatus";
import { WifiOffIcon } from "lucide-react";
import { Alert } from "react-bootstrap";

/*
|--------------------------------------------------------------------------
| AppLayout Component
|--------------------------------------------------------------------------
| Wraps all pages with header, navbar, footer, and toast notifications.
| Also tracks online/offline status using useOnlineStatus hook.
*/
export default function AppLayout({
    children,
    showHeaderLinks = true,
    showNavBar = true,
    showFooter = true,
    navBarFluid = false,
}) {
    const isOnline = useOnlineStatus();

    return (
        <>
            {/* Toast notifications */}
            <ToastContainer
                position="top-center"
                autoClose={2000}
                hideProgressBar={false}
            />

            {/* Online/Offline Status Banner */}
            {!isOnline && (
                <Alert
                    variant="danger"
                    className="offline-alert rounded-0 p-1 text-center"
                >
                    <WifiOffIcon size={18} className="me-2" />
                    You are offline. Reconnecting...
                </Alert>
            )}

            {/* Top header links */}
            {showHeaderLinks && <HeaderLinks />}

            {/* Navigation bar */}
            {showNavBar && <NavBar fluid={navBarFluid} />}

            {/* Main content */}
            <main>{children}</main>

            {/* Footer */}
            {showFooter && <Footer />}
        </>
    );
}

import Footer from "@/Components/Pages/Footer";
import HeaderLinks from "@/Components/Pages/HeaderLinks";
import NavBar from "@/Components/Pages/NavBar";
import { ToastContainer } from "react-toastify";

export default function AppLayout({ children }) {
    return (
        <>
            <ToastContainer
                position="top-center"
                autoClose={2000}
                hideProgressBar={false}
            />

            {/* Top links */}
            <HeaderLinks />

            {/* Nav bar */}
            <NavBar />

            {children}

            <Footer />
        </>
    );
}

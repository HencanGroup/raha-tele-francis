import Footer from '@/Components/Footer';
import HeaderLinks from '@/Components/HeaderLinks';
import NavBar from '@/Components/NavBar';
import { ToastContainer } from "react-toastify";

export default function AppLayout({ children }) {
    return (
        <>
            <ToastContainer position="top-right" autoClose={5000} hideProgressBar={false} />

            <HeaderLinks />

            <NavBar />

            {children}

            <Footer />
        </>
    );
}

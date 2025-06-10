import { Link, usePage } from "@inertiajs/react";
import { Container, Navbar, Nav, Offcanvas } from 'react-bootstrap';
import ApplicationLogo from "./ApplicationLogo";

export default function NavBar() {
    const { auth } = usePage().props;

    return (
        <Navbar expand="lg" className="escort-navbar">
            <Container>
                <Link href="/" className="navbar-brand">
                    <ApplicationLogo />
                </Link>

                <Navbar.Toggle aria-controls="offcanvas-navbar" />

                <Navbar.Offcanvas
                    id="offcanvas-navbar"
                    aria-labelledby="offcanvas-navbar-label"
                    placement="end"
                    className="escort-offcanvas"
                >
                    <Offcanvas.Header closeButton>
                        <Offcanvas.Title id="offcanvas-navbar-label">
                            Menu
                        </Offcanvas.Title>
                    </Offcanvas.Header>
                    <Offcanvas.Body>
                        <Nav className="ms-auto">
                            <Link href="/" className="nav-link active d-flex align-items-center">
                                <i className="bi bi-house-door me-2"></i> {/* Home icon */}
                                Home
                            </Link>
                            <Link href="/dating-advice" className="nav-link d-flex align-items-center">
                                <i className="bi bi-heart me-2"></i> {/* Heart/Advice icon */}
                                Dating Advice
                            </Link>
                            <Link href="/singles-near-me" className="nav-link d-flex align-items-center">
                                <i className="bi bi-geo-alt me-2"></i> {/* Location icon */}
                                Singles Near Me
                            </Link>
                            <Link href={route("plan.index")} className="nav-link d-flex align-items-center">
                                <i className="bi bi-box-seam me-2"></i> {/* Box/Plan icon */}
                                Plans
                            </Link>
                            {auth?.user ? (
                                <>
                                    <Link
                                        href={route('dashboard')}
                                        className="nav-link nav-cta d-flex align-items-center rounded-4"
                                    >
                                        <i className="bi bi-person-circle me-2"></i> {/* User/Account icon */}
                                        Account
                                    </Link>
                                    <Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                        className="nav-link nav-cta d-flex align-items-center rounded-4"
                                    >
                                        <i className="bi bi-box-arrow-right me-2"></i> {/* Logout icon */}
                                        Logout
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="nav-link nav-cta d-flex align-items-center rounded-4"
                                    >
                                        <i className="bi bi-person me-2"></i> {/* Login icon */}
                                        Login
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="nav-link nav-cta d-flex align-items-center rounded-4"
                                    >
                                        <i className="bi bi-person-plus me-2"></i> {/* Register icon */}
                                        Sign Up
                                    </Link>
                                </>
                            )}
                        </Nav>
                    </Offcanvas.Body>
                </Navbar.Offcanvas>
            </Container>
        </Navbar>
    );
}
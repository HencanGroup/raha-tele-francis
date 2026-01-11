import { Link, router, usePage } from "@inertiajs/react";
import {
    Container,
    Navbar,
    Nav,
    Offcanvas,
    Dropdown,
    Image,
} from "react-bootstrap";
import ApplicationLogo from "../Common/ApplicationLogo";
import { getProfileImage } from "@/Utils/helpers";

export default function NavBar({ fluid = false }) {
    const { auth } = usePage().props;
    const isAuthenticated = auth?.user;

    // Define all navigation items in one place
    const navItems = [
        // Public items (always visible)
        {
            href: "/",
            label: "🏠 Home",
            className: "nav-link d-flex align-items-center gap-2 rounded-3",
            type: "link",
            authRequired: null,
        },
        {
            href: route("escort.index", { search: "singles-near-me" }),
            label: "🌍 Singles Near Me",
            className: "nav-link d-flex align-items-center gap-2 rounded-3",
            type: "link",
            authRequired: null,
        },
        {
            href: route("conversation.index"),
            label: "💬 Chat",
            className: "nav-link d-flex align-items-center gap-2 rounded-3",
            type: "link",
            authRequired: null,
        },
    ];

    // Authenticated user dropdown items
    const authenticatedDropdownItems = [
        {
            href: route("dashboard"),
            label: "👤 Account",
            className: "dropdown-item d-flex align-items-center gap-2 py-2",
            type: "link",
        },
    ];

    // Guest dropdown items
    const guestDropdownItems = [
        {
            href: route("login"),
            label: "🔑 Login",
            className: "dropdown-item d-flex align-items-center gap-2 py-2",
            type: "link",
        },
        {
            href: route("register"),
            label: "📝 Sign Up",
            className: "dropdown-item d-flex align-items-center gap-2 py-2",
            type: "link",
        },
    ];

    // Filter items based on authentication status
    const filteredItems = navItems.filter((item) => {
        if (item.authRequired === null) return true;
        if (isAuthenticated) return item.authRequired === true;
        return item.authRequired === false;
    });

    // Handle logout
    const handleLogout = (e) => {
        e.preventDefault();
        router.post(route("logout"));
    };

    return (
        <Navbar
            expand="lg"
            className="escort-navbar shadow-sm py-1"
            sticky="top"
            data-bs-theme="dark"
        >
            <Container
                fluid={fluid ?? "lg"}
                className="d-flex align-items-center"
            >
                {/* Brand Logo */}
                <Navbar.Brand
                    as={Link}
                    href="/"
                    className="text-decoration-none me-auto"
                >
                    <ApplicationLogo className="text-white" />
                </Navbar.Brand>

                {/* Desktop Navigation - Always visible */}
                <Navbar.Collapse
                    id="navbar-collapse"
                    className="justify-content-end d-none d-lg-flex"
                >
                    <Nav className="align-items-center gap-3">
                        {filteredItems.map((item, index) => (
                            <Nav.Item
                                key={index}
                                className={
                                    item.type === "button" ? "d-flex" : ""
                                }
                            >
                                <Link
                                    href={item.href}
                                    className={item.className}
                                    method={item.method || "get"}
                                    as={item.as || "a"}
                                >
                                    {item.label}
                                </Link>
                            </Nav.Item>
                        ))}

                        {/* Authenticated User Dropdown */}
                        {isAuthenticated && (
                            <Dropdown align={"end"}>
                                <Dropdown.Toggle
                                    variant="outline-light"
                                    className="d-flex align-items-center gap-2 rounded-4 hover-scale"
                                >
                                    <Image
                                        src={getProfileImage(
                                            auth?.user?.profile
                                        )}
                                        alt={auth?.user?.name}
                                        className="rounded-circle"
                                        style={{
                                            width: "32px",
                                            height: "32px",
                                        }}
                                    />
                                </Dropdown.Toggle>
                                <Dropdown.Menu className="bg-dark border-secondary">
                                    {authenticatedDropdownItems.map(
                                        (item, index) => (
                                            <Dropdown.Item
                                                key={index}
                                                as={Link}
                                                href={item.href}
                                                className={item.className}
                                            >
                                                {item.label}
                                            </Dropdown.Item>
                                        )
                                    )}
                                    <Dropdown.Divider className="border-secondary" />
                                    <Dropdown.Item
                                        as="button"
                                        onClick={handleLogout}
                                        className="dropdown-item d-flex align-items-center gap-2 py-2"
                                    >
                                        🚪 Logout
                                    </Dropdown.Item>
                                </Dropdown.Menu>
                            </Dropdown>
                        )}

                        {/* Guest Dropdown */}
                        {!isAuthenticated && (
                            <Dropdown align={"end"}>
                                <Dropdown.Toggle
                                    variant="light"
                                    className="d-flex align-items-center gap-2 rounded-4 hover-scale text-dark"
                                >
                                    🔑 Login
                                </Dropdown.Toggle>
                                <Dropdown.Menu className="bg-dark border-secondary">
                                    {guestDropdownItems.map((item, index) => (
                                        <Dropdown.Item
                                            key={index}
                                            as={Link}
                                            href={item.href}
                                            className={item.className}
                                        >
                                            {item.label}
                                        </Dropdown.Item>
                                    ))}
                                </Dropdown.Menu>
                            </Dropdown>
                        )}
                    </Nav>
                </Navbar.Collapse>

                {/* Mobile Toggle */}
                <Navbar.Toggle
                    aria-controls="offcanvas-navbar"
                    className="border-0 d-lg-none"
                >
                    <span className="navbar-toggler-icon"></span>
                </Navbar.Toggle>

                {/* Offcanvas Menu - Mobile Only */}
                <Navbar.Offcanvas
                    id="offcanvas-navbar"
                    aria-labelledby="offcanvas-navbar-label"
                    placement="end"
                    className="escort-offcanvas d-lg-none"
                >
                    <Offcanvas.Header
                        className="border-secondary border-bottom"
                        closeButton
                        closeVariant="white"
                    >
                        <Offcanvas.Title
                            id="offcanvas-navbar-label"
                            className="fw-bold"
                        >
                            🍔 Menu
                        </Offcanvas.Title>
                    </Offcanvas.Header>

                    <Offcanvas.Body>
                        <Nav className="flex-column gap-2">
                            {filteredItems.map((item, index) => (
                                <Nav.Item key={index}>
                                    <Link
                                        href={item.href}
                                        className={`${item.className} ${
                                            item.type === "button"
                                                ? "justify-content-center"
                                                : ""
                                        }`}
                                        method={item.method || "get"}
                                        as={item.as || "a"}
                                    >
                                        {item.label}
                                    </Link>
                                </Nav.Item>
                            ))}

                            {/* Authenticated User Section - Mobile */}
                            {isAuthenticated && (
                                <>
                                    <div className="dropdown-divider border-secondary my-2"></div>
                                    <h6 className="text-white-50 px-3 mb-2">
                                        Account
                                    </h6>
                                    {authenticatedDropdownItems.map(
                                        (item, index) => (
                                            <Nav.Item
                                                key={`auth-mobile-${index}`}
                                            >
                                                <Link
                                                    href={item.href}
                                                    className={`${item.className} px-3`}
                                                >
                                                    {item.label}
                                                </Link>
                                            </Nav.Item>
                                        )
                                    )}
                                    <Nav.Item>
                                        <form
                                            onSubmit={handleLogout}
                                            className="w-100"
                                        >
                                            <button
                                                type="submit"
                                                className="dropdown-item d-flex align-items-center gap-2 py-2 px-3 w-100 text-start border-0 bg-transparent"
                                            >
                                                🚪 Logout
                                            </button>
                                        </form>
                                    </Nav.Item>
                                </>
                            )}

                            {/* Guest Section - Mobile */}
                            {!isAuthenticated && (
                                <>
                                    <div className="dropdown-divider border-secondary my-2"></div>
                                    <h6 className="text-white-50 px-3 mb-2">
                                        Guest
                                    </h6>
                                    {guestDropdownItems.map((item, index) => (
                                        <Nav.Item key={`guest-mobile-${index}`}>
                                            <Link
                                                href={item.href}
                                                className={`${item.className} px-3`}
                                            >
                                                {item.label}
                                            </Link>
                                        </Nav.Item>
                                    ))}
                                </>
                            )}
                        </Nav>
                    </Offcanvas.Body>
                </Navbar.Offcanvas>
            </Container>
        </Navbar>
    );
}

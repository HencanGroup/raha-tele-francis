import { Container, Row, Col, Nav, Image } from "react-bootstrap";
import { Link, usePage } from "@inertiajs/react";
import ConversationList from "@/Components/Chat/ConversationList";
import { getProfileImage } from "@/Utils/helpers";
import React, { useState, useRef, useEffect } from "react";

const SIDEBAR_WIDTH = 70;

const ChatLayout = ({ children, conversations }) => {
    const { url, auth } = usePage().props;
    const [showTooltip, setShowTooltip] = useState(null);
    const tooltipTimeoutRef = useRef(null);

    const user = auth?.user || {};

    const navItems = [
        {
            icon: "bi-chat-dots-fill",
            label: "Chats",
            href: "/chat",
            active: url?.startsWith("/chat"),
        },
    ];

    const bottomNavItems = [
        {
            icon: "bi-gear-fill",
            label: "Settings",
            href: "/settings",
            active: url === "/settings",
        },
        {
            icon: "profile",
            label: "Profile",
            href: "/profile",
            active: url === "/profile",
        },
    ];

    const handleMouseEnter = (key) => {
        clearTimeout(tooltipTimeoutRef.current);
        tooltipTimeoutRef.current = setTimeout(() => {
            setShowTooltip(key);
        }, 250);
    };

    const handleMouseLeave = () => {
        clearTimeout(tooltipTimeoutRef.current);
        setShowTooltip(null);
    };

    useEffect(() => {
        return () => clearTimeout(tooltipTimeoutRef.current);
    }, []);

    const renderNavItem = (item, key) => (
        <div
            key={key}
            className="position-relative"
            onMouseEnter={() => handleMouseEnter(key)}
            onMouseLeave={handleMouseLeave}
        >
            <Nav.Link
                as={Link}
                href={item.href}
                className={`d-flex justify-content-center align-items-center p-2 border-0 ${
                    item.active
                        ? "text-primary"
                        : "text-secondary hover-text-white"
                }`}
            >
                {item.icon === "profile" ? (
                    <Image
                        src={getProfileImage(user)}
                        alt={user.name || "Profile"}
                        roundedCircle
                        width={34}
                        height={34}
                        style={{ objectFit: "cover" }}
                    />
                ) : (
                    <i className={`bi ${item.icon} fs-5`} />
                )}
            </Nav.Link>

            {showTooltip === key && (
                <div
                    className="position-absolute bg-dark text-white px-2 py-1 rounded small"
                    style={{
                        left: "100%",
                        top: "50%",
                        transform: "translateY(-50%)",
                        whiteSpace: "nowrap",
                        zIndex: 1000,
                        border: "1px solid rgba(255,255,255,0.1)",
                        boxShadow: "0 4px 10px rgba(0,0,0,0.4)",
                    }}
                >
                    {item.label}
                </div>
            )}
        </div>
    );

    return (
        <Container fluid className="vh-100 bg-dark p-0 overflow-hidden">
            <Row className="h-100 g-0">
                {/* LEFT SIDE */}
                <Col
                    md={4}
                    className="d-flex h-100 p-0 border-end border-secondary"
                >
                    {/* Sidebar */}
                    <aside
                        className="d-flex flex-column bg-black py-3"
                        style={{ width: SIDEBAR_WIDTH }}
                    >
                        <Nav className="flex-column flex-grow-1">
                            {navItems.map((item, index) =>
                                renderNavItem(item, `nav-${index}`),
                            )}
                        </Nav>

                        <div className="mt-auto">
                            {bottomNavItems.map((item, index) =>
                                renderNavItem(item, `bottom-${index}`),
                            )}
                        </div>
                    </aside>

                    {/* Conversation List */}
                    <div className="flex-grow-1 bg-dark h-100 overflow-hidden px-2">
                        <ConversationList
                            conversations={conversations || []}
                            className="h-100 border-0"
                        />
                    </div>
                </Col>

                {/* RIGHT SIDE (CHAT CONTENT) */}
                <Col md={8} className="h-100 p-0 bg-dark overflow-hidden">
                    {children}
                </Col>
            </Row>
        </Container>
    );
};

export default ChatLayout;

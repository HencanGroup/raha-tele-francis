import { Container, Row, Col } from "react-bootstrap";

export default function HeaderLinks() {
    const socialLinks = [
        { name: "WhatsApp", emoji: "💬", href: "#" },
        { name: "Twitter", emoji: "🐦", href: "#" },
        { name: "Instagram", emoji: "📷", href: "#" },
        { name: "Telegram", emoji: "📨", href: "#" },
    ];

    const contactInfo = [
        {
            type: "phone",
            value: "+254 715 023 132",
            href: "tel:+254715023132",
            emoji: "📞",
            className: "contact-link",
        },
        {
            type: "location",
            value: "Nairobi, Kenya",
            href: "#location",
            emoji: "📍",
            className: "contact-link d-none d-md-inline-block",
        },
        {
            type: "email",
            value: "support@raha-tele.com",
            href: "mailto:support@raha-tele.com",
            emoji: "✉️",
            className: "contact-link",
        },
    ];

    return (
        <div className="header-top-bar d-md-block">
            <Container>
                <Row className="align-items-center">
                    {/* Contact Info Section */}
                    <Col md={8}>
                        <div className="contact-info-container">
                            {contactInfo.map((contact) => (
                                <a
                                    key={contact.type}
                                    href={contact.href}
                                    className={contact.className}
                                    aria-label={contact.type}
                                >
                                    <span className="contact-emoji">
                                        {contact.emoji}
                                    </span>
                                    <span className="contact-text">
                                        {contact.value}
                                    </span>
                                </a>
                            ))}
                        </div>
                    </Col>

                    {/* Social Links Section */}
                    <Col
                        md={4}
                        className="d-none d-md-flex justify-content-end"
                    >
                        <div className="social-section">
                            <span className="social-label">Find Us:</span>
                            <div className="social-links-container">
                                {socialLinks.map((social) => (
                                    <a
                                        key={social.name}
                                        href={social.href}
                                        className="social-link"
                                        aria-label={`Follow us on ${social.name}`}
                                    >
                                        {social.emoji}
                                    </a>
                                ))}
                            </div>
                        </div>
                    </Col>
                </Row>
            </Container>
        </div>
    );
}

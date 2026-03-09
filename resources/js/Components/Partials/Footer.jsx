import React from "react";
import { Container, Row, Col, Button, Form } from "react-bootstrap";

export default function Footer() {
    const quickLinks = [
        { title: "Home", url: "/", icon: "🏠" },
        { title: "Browse Companions", url: "/escorts", icon: "👥" },
        { title: "Singles Near Me", url: "/singles-near-me", icon: "📍" },
        { title: "Premium Membership", url: "/premium", icon: "⭐" },
        { title: "Success Stories", url: "/testimonials", icon: "💖" },
        { title: "Safety Guide", url: "/safety", icon: "🛡️" },
        { title: "About Us", url: "/about", icon: "ℹ️" },
    ];

    const legalLinks = [
        { title: "Terms of Service", url: "/terms" },
        { title: "Privacy Policy", url: "/privacy" },
        { title: "Cookie Policy", url: "/cookies" },
        { title: "Contact Support", url: "/contact", icon: "📞" },
    ];

    const cities = [
        { name: "Nairobi", url: "/nairobi" },
        { name: "Mombasa", url: "/mombasa" },
        { name: "Kisumu", url: "/kisumu" },
        { name: "Nakuru", url: "/nakuru" },
        { name: "Eldoret", url: "/eldoret" },
        { name: "Malindi", url: "/malindi" },
        { name: "Diani", url: "/diani" },
        { name: "Thika", url: "/thika" },
    ];

    return (
        <footer
            className="luxury-footer bg-dark text-white pt-5"
            data-bs-theme="dark"
        >
            <Container fluid="lg">
                {/* Decorative top border */}
                <div className="footer-top-border mb-5">
                    <div className="d-flex justify-content-center">
                        <div className="footer-divider"></div>
                    </div>
                </div>

                {/* Main Footer Content */}
                <Row className="footer-main pb-5">
                    {/* Brand Section */}
                    <Col lg={4} className="mb-5 mb-lg-0">
                        <div className="footer-brand pe-lg-4">
                            <h2 className="footer-logo">
                                <span className="text-gold">Raha</span>
                                <span className="text-white">Tele</span>
                                <span className="footer-badge">KE</span>
                            </h2>
                            <p className="small mb-3 text-warning">
                                Connect . Relax . Enjoy
                            </p>
                            <p className="footer-description mb-4 opacity-75">
                                Kenya's premier platform connecting discerning
                                individuals with exceptional companions.
                                Experience luxury, discretion, and authentic
                                connections.
                            </p>

                            {/* Social Links */}
                            <div className="social-links d-flex gap-3">
                                {[
                                    {
                                        icon: "whatsapp",
                                        label: "WhatsApp",
                                        color: "#25D366",
                                    },
                                    {
                                        icon: "facebook",
                                        label: "Facebook",
                                        color: "#1877F2",
                                    },
                                    {
                                        icon: "instagram",
                                        label: "Instagram",
                                        color: "#E4405F",
                                    },
                                    {
                                        icon: "twitter",
                                        label: "Twitter",
                                        color: "#1DA1F2",
                                    },
                                    {
                                        icon: "telegram",
                                        label: "Telegram",
                                        color: "#0088CC",
                                    },
                                ].map((social, index) => (
                                    <a
                                        key={index}
                                        href="#"
                                        className="social-link d-flex align-items-center justify-content-center"
                                        aria-label={social.label}
                                        style={{
                                            "--social-color": social.color,
                                        }}
                                    >
                                        <i
                                            className={`bi bi-${social.icon}`}
                                        ></i>
                                    </a>
                                ))}
                            </div>
                        </div>
                    </Col>

                    {/* Quick Links & Cities */}
                    <Col lg={8}>
                        <Row>
                            {/* Quick Links */}
                            <Col md={6} lg={4} className="mb-5 mb-lg-0">
                                <h4 className="footer-heading mb-4">
                                    <i className="bi bi-lightning-charge-fill me-2 text-gold"></i>
                                    Quick Links
                                </h4>
                                <ul className="footer-links list-unstyled">
                                    {quickLinks.map((link, index) => (
                                        <li key={index} className="mb-3">
                                            <a
                                                href={link.url}
                                                className="footer-link d-flex align-items-center gap-2"
                                            >
                                                <span className="link-icon">
                                                    {link.icon}
                                                </span>
                                                <span className="link-text">
                                                    {link.title}
                                                </span>
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </Col>

                            {/* Popular Cities */}
                            <Col md={6} lg={4} className="mb-5 mb-lg-0">
                                <h4 className="footer-heading mb-4">
                                    <i className="bi bi-geo-alt-fill me-2 text-gold"></i>
                                    Explore Kenya
                                </h4>
                                <div className="city-grid d-grid gap-2">
                                    {cities.map((city, index) => (
                                        <a
                                            key={index}
                                            href={city.url}
                                            className="city-link d-flex align-items-center justify-content-between p-2 rounded"
                                        >
                                            <span>{city.name}</span>
                                            <i className="bi bi-chevron-right opacity-50"></i>
                                        </a>
                                    ))}
                                </div>
                            </Col>

                            {/* Newsletter & Contact */}
                            <Col lg={4}>
                                <h4 className="footer-heading mb-4">
                                    <i className="bi bi-envelope-fill me-2 text-gold"></i>
                                    Stay Updated
                                </h4>

                                {/* Newsletter Form */}
                                <Form className="newsletter-form mb-4">
                                    <p className="mb-3 opacity-75">
                                        Get exclusive updates, offers, and
                                        premium content directly.
                                    </p>
                                    <div className="input-group mb-3">
                                        <Form.Control
                                            type="email"
                                            placeholder="Enter your email"
                                            className="bg-dark text-white border-dotted border-dark"
                                            required
                                        />
                                        <Button
                                            variant="gold"
                                            type="submit"
                                            className="d-flex align-items-center gap-2"
                                        >
                                            <i className="bi bi-send-fill"></i>
                                        </Button>
                                    </div>
                                    <Form.Text className="text-muted">
                                        We respect your privacy. Unsubscribe
                                        anytime.
                                    </Form.Text>
                                </Form>

                                {/* Contact Info */}
                                <div className="contact-info mt-4">
                                    <h5 className="footer-subheading mb-3">
                                        <i className="bi bi-headset me-2 text-gold"></i>
                                        24/7 Support
                                    </h5>
                                    <div className="contact-item d-flex align-items-center gap-2 mb-2">
                                        <i className="bi bi-whatsapp text-success"></i>
                                        <a
                                            href="tel:+254715023132"
                                            className="text-white text-decoration-none"
                                        >
                                            +254 715 023 132
                                        </a>
                                    </div>
                                    <div className="contact-item d-flex align-items-center gap-2">
                                        <i className="bi bi-envelope text-gold"></i>
                                        <a
                                            href="mailto:support@rahatele.co.ke"
                                            className="text-white text-decoration-none"
                                        >
                                            support@rahatele.co.ke
                                        </a>
                                    </div>
                                </div>
                            </Col>
                        </Row>
                    </Col>
                </Row>

                {/* Payment Methods */}
                <Row className="payment-section py-4 border-top-dashed">
                    <Col className="text-center">
                        {/* <p className="payment-title mb-3 opacity-75">
                            Secure Payment Methods
                        </p> */}
                        <div className="payment-icons d-flex justify-content-center flex-wrap gap-4">
                            {[
                                { icon: "credit-card", label: "Credit Cards" },
                                { icon: "paypal", label: "PayPal" },
                                { icon: "phone", label: "M-Pesa" },
                                {
                                    icon: "shield-check",
                                    label: "Secure Payment",
                                },
                                { icon: "currency-bitcoin", label: "Crypto" },
                            ].map((method, index) => (
                                <div
                                    key={index}
                                    className="payment-method d-flex flex-column align-items-center"
                                >
                                    <i
                                        className={`bi bi-${method.icon} payment-icon`}
                                    ></i>
                                    <span className="payment-label mt-1">
                                        {method.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </Col>
                </Row>

                {/* Footer Bottom */}
                <Row className="footer-bottom py-4 border-top-dashed">
                    <Col md={6} className="mb-3 mb-md-0">
                        <p className="copyright text-center text-md-start mb-0">
                            © {new Date().getFullYear()} Raha Tele Kenya. All
                            rights reserved.
                            <span className="d-block d-md-inline ms-md-2 opacity-75">
                                Platform for adults 18+
                            </span>
                        </p>
                    </Col>
                    <Col md={6}>
                        <div className="legal-links d-flex flex-wrap justify-content-md-end gap-3">
                            {legalLinks.map((link, index) => (
                                <a
                                    key={index}
                                    href={link.url}
                                    className="legal-link text-decoration-none d-flex align-items-center gap-1"
                                >
                                    {link.icon && <span>{link.icon}</span>}
                                    {link.title}
                                </a>
                            ))}
                        </div>
                    </Col>
                </Row>
            </Container>
        </footer>
    );
}

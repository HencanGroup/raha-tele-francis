import React from 'react';
import { Container, Row, Col, Button } from 'react-bootstrap';

export default function Footer() {
    const quickLinks = [
        { title: 'Home', url: '/' },
        { title: 'Browse Companions', url: '/escorts' },
        { title: 'Membership', url: '/premium' },
        { title: 'Success Stories', url: '/testimonials' },
        { title: 'Safety Guide', url: '/safety' },
        { title: 'About Us', url: '/about' } // Added About Us link
    ];

    const legalLinks = [
        { title: 'Terms of Service', url: '/terms' },
        { title: 'Privacy Policy', url: '/privacy' },
        { title: 'Cookie Policy', url: '/cookies' },
        { title: 'Disclaimer', url: '/disclaimer' } // Changed GDPR to Disclaimer for broader relevance
    ];

    // Kenya-specific cities
    const cities = [
        'Nairobi', 'Mombasa', 'Kisumu', 'Nakuru',
        'Eldoret', 'Malindi', 'Diani', 'Thika'
    ];

    return (
        <footer className="luxury-footer py-5">
            <Container>
                {/* Main Footer Content */}
                <Row className="footer-main py-5">
                    {/* Brand Info */}
                    <Col lg={4} className="mb-5 mb-lg-0">
                        <div className="footer-brand">
                            <h3 className="footer-logo">Raha<span className="gold-text">Tele</span></h3>
                            <p className="footer-description">
                                The premier platform connecting discerning clients with exceptional companions across Kenya.
                            </p>
                            <div className="social-links d-flex gap-3 mt-3"> {/* Added d-flex and gap-3 for spacing */}
                                <a href="#" aria-label="Whatsapp"><i className="bi bi-whatsapp fs-4"></i></a> {/* Larger icon */}
                                <a href="#" aria-label="Facebook"><i className="bi bi-facebook fs-4"></i></a> {/* Added Facebook */}
                                <a href="#" aria-label="Instagram"><i className="bi bi-instagram fs-4"></i></a>
                                <a href="#" aria-label="Twitter"><i className="bi bi-twitter-x fs-4"></i></a>
                                <a href="#" aria-label="Telegram"><i className="bi bi-telegram fs-4"></i></a>
                            </div>
                        </div>
                    </Col>

                    {/* Quick Links */}
                    <Col md={4} lg={2} className="mb-5 mb-md-0">
                        <h4 className="footer-heading">Quick Links</h4>
                        <ul className="footer-links list-unstyled"> {/* Added list-unstyled for no default list styling */}
                            {quickLinks.map((link, index) => (
                                <li key={index} className="mb-2"> {/* Added margin-bottom for spacing */}
                                    <a href={link.url} className="text-decoration-none">{link.title}</a> {/* Removed underline */}
                                </li>
                            ))}
                        </ul>
                    </Col>

                    {/* Popular Cities */}
                    <Col md={4} lg={3} className="mb-5 mb-md-0">
                        <h4 className="footer-heading">Popular Cities</h4>
                        <div className="city-grid d-grid gap-2"> {/* Used d-grid for a more responsive grid layout */}
                            {cities.map((city, index) => (
                                <a key={index} href={`/escorts/${city.toLowerCase().replace(' ', '-')}`} className="text-decoration-none">
                                    {city}
                                </a>
                            ))}
                        </div>
                    </Col>

                    {/* Newsletter & Contact */}
                    <Col md={4} lg={3}>
                        <h4 className="footer-heading">Stay Updated</h4>
                        <form className="newsletter-form mb-4">
                            <div className="input-group">
                                <input
                                    type="email"
                                    placeholder="Your email address"
                                    className="form-control"
                                    required
                                />
                                <Button
                                    variant="primary"
                                    type="submit" // Changed to type="submit" for form
                                >
                                    <i className="bi bi-send-fill"></i> {/* Filled send icon */}
                                </Button>
                            </div>
                            <p className="newsletter-note mt-2">
                                Receive exclusive offers and updates from Raha Tele.
                            </p>
                        </form>

                        <div className="payment-methods mt-4">
                            <p className="payment-title fw-bold">Accepted Payments:</p>
                            <div className="payment-icons d-flex gap-3"> {/* Added d-flex and gap-3 for spacing */}
                                <i className="bi bi-credit-card fs-3"></i> {/* Larger icons */}
                                <i className="bi bi-paypal fs-3"></i> {/* Added PayPal for common online payments */}
                                <i className="bi bi-currency-bitcoin fs-3"></i>
                                {/* M-Pesa icon - commonly represented by a mobile payment icon or a custom one if available */}
                                <i className="bi bi-phone-fill fs-3" title="M-Pesa"></i> {/* Representative icon for M-Pesa */}
                            </div>
                        </div>
                    </Col>
                </Row>

                {/* Footer Bottom */}
                <Row className="footer-bottom py-3 border-top pt-4"> {/* Added border-top and pt-4 */}
                    <Col md={6} className="text-center text-md-start mb-3 mb-md-0 px-0">
                        <p className="copyright mb-0">
                            © {new Date().getFullYear()} Raha Tele (Kenya). All rights reserved.
                        </p>
                    </Col>
                    <Col md={6} className="text-center text-md-end px-0">
                        <ul className="legal-links list-unstyled d-flex justify-content-center justify-content-md-end gap-3 mb-0"> {/* Used d-flex, justify-content-center/end, gap-3 */}
                            {legalLinks.map((link, index) => (
                                <li key={index}>
                                    <a href={link.url} className="text-decoration-none">{link.title}</a>
                                    {index < legalLinks.length - 1 && <span className="mx-2 text-muted">|</span>} {/* Improved divider with spacing and color */}
                                </li>
                            ))}
                        </ul>
                    </Col>
                </Row>
            </Container>
        </footer>
    );
}
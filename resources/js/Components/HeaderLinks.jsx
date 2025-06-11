import { Link } from '@inertiajs/react';
import { Container, Row, Col } from 'react-bootstrap';

export default function HeaderLinks() {
    return (
        <div className="header-top-bar d-none d-md-block">
            <Container>
                <Row className="align-items-center">
                    <Col md={8}>
                        <div className="contact-info">
                            <a href="tel:+254715023132" className="contact-link d-inline-flex align-items-center">
                                <i className="bi bi-telephone me-2"></i> {/* Phone icon */}
                                +254 715 023 132
                            </a>
                            <a href="#" className="contact-link d-inline-flex align-items-center">
                                <i className="bi bi-geo-alt-fill me-2"></i> {/* Filled location icon */}
                                Nairobi, Kenya
                            </a>
                            <a href="mailto:support@raha-tele.com" className="contact-link d-inline-flex align-items-center">
                                <i className="bi bi-envelope-fill me-2"></i> {/* Filled envelope icon */}
                                support@raha-tele.com
                            </a>
                        </div>
                    </Col>
                    <Col md={4}>
                        <div className="d-flex align-items-center justify-content-end social-container">
                            <span className="social-label me-3">Find Us On:</span>
                            <div className="social-links">
                                <a href="#" className="social-link">
                                    <i className="bi bi-whatsapp"></i>
                                </a>
                                <a href="#" className="social-link">
                                    <i className="bi bi-twitter-x"></i>
                                </a>
                                <a href="#" className="social-link">
                                    <i className="bi bi-instagram"></i>
                                </a>
                                <a href="#" className="social-link">
                                    <i className="bi bi-telegram"></i>
                                </a>
                            </div>
                        </div>
                    </Col>
                </Row>
            </Container>
        </div>
    );
}
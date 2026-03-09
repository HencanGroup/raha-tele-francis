import { Container, Row, Col } from "react-bootstrap";
import AppLayout from "./AppLayout";

export default function AuthLayout({ children }) {
    return (
        <AppLayout>
            <Container className="auth-layout min-vh-100 d-flex align-items-center py-5 justify-content-center">
                <Row className="justify-content-center align-items-center">
                    {/* Auth Form Side */}
                    <Col lg={6} xl={5} className="mb-4 mb-lg-0">
                        <div className="auth-card">
                            {/* Card Body */}
                            <div className="auth-card-body p-4">
                                <div className="auth-icon-wrapper mb-3">
                                    <div className="auth-icon">
                                        <i className="bi bi-shield-lock-fill"></i>
                                    </div>
                                </div>

                                {children}

                                {/* Social Login Options */}
                                <div className="social-auth mt-4 pt-3 border-top border-light border-opacity-10">
                                    <p className="text-center mb-2 small opacity-75">
                                        Or continue with
                                    </p>
                                    <div className="social-buttons d-flex justify-content-center gap-2">
                                        <button className="social-btn google">
                                            <i className="bi bi-google"></i>
                                        </button>
                                        <button className="social-btn facebook">
                                            <i className="bi bi-facebook"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Card Footer */}
                            <div className="auth-card-footer text-center p-3 border-top border-light border-opacity-10">
                                <small className="opacity-75">
                                    By continuing, you agree to our{" "}
                                    <a
                                        href="/terms"
                                        className="text-warning text-decoration-none"
                                    >
                                        Terms
                                    </a>{" "}
                                    and{" "}
                                    <a
                                        href="/privacy"
                                        className="text-warning text-decoration-none"
                                    >
                                        Privacy Policy
                                    </a>
                                </small>
                            </div>
                        </div>
                    </Col>

                    {/* Features Side Panel */}
                    <Col lg={6} xl={7}>
                        <div className="features-panel ps-lg-4">
                            <div className="premium-badge rounded-pill mb-3">
                                <div className="d-inline-flex align-items-center gap-2 bg-primary bg-opacity-10 px-3 py-1 rounded-pill">
                                    <i className="bi bi-star-fill text-warning"></i>
                                    <span className="fw-semibold small">
                                        PREMIUM EXPERIENCE
                                    </span>
                                </div>
                            </div>

                            <h2 className="panel-title fw-bold mb-4">
                                Join Kenya's{" "}
                                <span className="text-warning">Elite</span>{" "}
                                Dating Community
                            </h2>

                            {/* Features Grid */}
                            <Row className="g-3 mb-4">
                                <Col md={6}>
                                    <div className="feature-box p-3 rounded h-100">
                                        <div className="d-flex align-items-start gap-2 mb-2">
                                            <i className="bi bi-shield-check text-success fs-5"></i>
                                            <h6 className="mb-0">
                                                Verified Profiles
                                            </h6>
                                        </div>
                                        <small className="opacity-75">
                                            All members thoroughly verified
                                        </small>
                                    </div>
                                </Col>
                                <Col md={6}>
                                    <div className="feature-box p-3 rounded h-100">
                                        <div className="d-flex align-items-start gap-2 mb-2">
                                            <i className="bi bi-lightning-charge-fill text-warning fs-5"></i>
                                            <h6 className="mb-0">
                                                Instant Matching
                                            </h6>
                                        </div>
                                        <small className="opacity-75">
                                            Connect with compatible matches
                                        </small>
                                    </div>
                                </Col>
                                <Col md={6}>
                                    <div className="feature-box p-3 rounded h-100">
                                        <div className="d-flex align-items-start gap-2 mb-2">
                                            <i className="bi bi-lock-fill text-warning fs-5"></i>
                                            <h6 className="mb-0">
                                                Complete Privacy
                                            </h6>
                                        </div>
                                        <small className="opacity-75">
                                            Your privacy is our priority
                                        </small>
                                    </div>
                                </Col>
                                <Col md={6}>
                                    <div className="feature-box p-3 rounded h-100">
                                        <div className="d-flex align-items-start gap-2 mb-2">
                                            <i className="bi bi-star-fill text-info fs-5"></i>
                                            <h6 className="mb-0">
                                                Premium Support
                                            </h6>
                                        </div>
                                        <small className="opacity-75">
                                            24/7 dedicated support
                                        </small>
                                    </div>
                                </Col>
                            </Row>

                            {/* Stats */}
                            <div className="premium-stats mb-4">
                                <div className="row g-3">
                                    <div className="col-6">
                                        <div className="stat-box p-3 rounded text-center">
                                            <div className="stat-number fw-bold display-6">
                                                10K+
                                            </div>
                                            <div className="stat-label opacity-75">
                                                Active Members
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-6">
                                        <div className="stat-box p-3 rounded text-center">
                                            <div className="stat-number fw-bold display-6">
                                                98%
                                            </div>
                                            <div className="stat-label opacity-75">
                                                Satisfaction Rate
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Testimonial */}
                            <div className="testimonial mt-4 p-3 rounded bg-light bg-opacity-10">
                                <div className="d-flex align-items-center gap-3 mb-3">
                                    <div className="testimonial-avatar">
                                        <img
                                            src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=80&q=80"
                                            alt="User"
                                            className="rounded-circle"
                                            width="50"
                                            height="50"
                                        />
                                    </div>
                                    <div>
                                        <h6 className="mb-0">Sarah M.</h6>
                                        <small className="opacity-75">
                                            Nairobi
                                        </small>
                                    </div>
                                </div>
                                <blockquote className="testimonial-text mb-0">
                                    <i className="bi bi-quote text-warning me-2"></i>
                                    RahaTele transformed my dating life. Met
                                    amazing, genuine people.
                                </blockquote>
                            </div>
                        </div>
                    </Col>
                </Row>
            </Container>
        </AppLayout>
    );
}

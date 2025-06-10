import { Col, Container, Row } from 'react-bootstrap';

export default function GuestLayout({ children }) {
    return (
        <Container className="d-flex justify-content-center align-items-center vh-100">
            <Col md={12} className='auth-container p-5'>
                <Row>
                    <Col md={7} className="p-3">
                        {children}
                    </Col>
                    <Col>
                        <img src="/storage/images/01.png" alt="auth-img" className="img-fluid auth-img" />
                    </Col>
                </Row>
            </Col>
        </Container>
    );
}

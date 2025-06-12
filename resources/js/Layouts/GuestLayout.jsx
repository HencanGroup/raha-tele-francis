import { Col, Container, Row } from 'react-bootstrap';
import AppLayout from './AppLayout';

export default function GuestLayout({ children }) {
    return (
        <AppLayout>
            <Container className="d-flex justify-content-center align-items-center py-5">
                <Col md={12} className='auth-container p-5'>
                    <Row>
                        <Col md={7} className="p-3">
                            {children}
                        </Col>
                        <Col>
                            <img src="/storage/images/bg/01.png" alt="auth-img" className="img-fluid auth-img" />
                        </Col>
                    </Row>
                </Col>
            </Container>
        </AppLayout>
    );
}

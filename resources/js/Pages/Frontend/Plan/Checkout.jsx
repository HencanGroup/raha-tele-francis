import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Container, Row, Col, Button, Alert, Spinner, Form } from 'react-bootstrap';
import { FiArrowLeft, FiPhone, FiShield, FiLogIn } from 'react-icons/fi';
import axios from 'axios';

export default function Checkout() {
    const { auth } = usePage().props;
    const [cart, setCart] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [formData, setFormData] = useState({
        phone: '',
    });

    useEffect(() => {
        const storedCart = localStorage.getItem('cart');

        if (!storedCart) {
            setError('No subscription plan selected. Please choose a plan first.');
            setLoading(false);
            return;
        }

        try {
            const parsedCart = JSON.parse(storedCart);
            setCart(parsedCart);

            // Set phone number if user is authenticated
            if (auth.user) {
                setFormData(prev => ({
                    ...prev,
                    phone: auth.user.phone || ''
                }));
            }

            setLoading(false);
        } catch (err) {
            setError('Invalid cart data. Please select a plan again.');
            setLoading(false);
        }
    }, [auth.user]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);

        if (!auth.user) {
            router.visit('/login', {
                data: { redirect: window.location.pathname }
            });
            return;
        }

        // Validate phone number
        if (!formData.phone || formData.phone.length !== 9 || !/^[0-9]+$/.test(formData.phone)) {
            setError('Please enter a valid M-Pesa phone number (7XX XXX XXX format)');
            return;
        }

        setProcessing(true);

        try {
            const response = await axios.post(route('mpesa.pay'), {
                phone: `254${formData.phone}`,
                amount: cart.total,
                plan: cart.items[0].name
            });

            // Check if the response indicates success
            if (response.data && response.data.success) {
                setSuccess(response.data.success || "Payment initiated successfully. Please complete the payment on your M-Pesa app.");
                setFormData({ phone: '' }); // Clear the phone input after successful submission
            } else {
                setError(response.data.error || "Payment initiation failed. Please try again.");
            }
        } catch (error) {
            let message = "Failed to initiate payment. Please try again.";
            if (error.response.data.error) {
                message = error.response.data.error;
            }
            setError(message);
        } finally {
            setProcessing(false);
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <Head title="Checkout" />
                <Container className="my-5 py-5 text-center">
                    <Spinner animation="border" variant="primary" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </Spinner>
                    <p className="mt-3">Loading your checkout details...</p>
                </Container>
            </AppLayout>
        );
    }

    if (error && !cart) {
        return (
            <AppLayout>
                <Head title="Checkout Error" />
                <Container className="my-5">
                    <Alert variant="danger" className="text-center">
                        <i className="bi bi-exclamation-triangle-fill me-2"></i>
                        {error}
                    </Alert>
                    <div className="text-center mt-4">
                        <Button
                            variant="primary"
                            onClick={() => router.visit('/subscription/plans')}
                            className="px-4 py-2 rounded-pill"
                        >
                            <FiArrowLeft className="me-2" />
                            Back to Plans
                        </Button>
                    </div>
                </Container>
            </AppLayout>
        );
    }

    if (!auth.user) {
        return (
            <AppLayout>
                <Head title="Login Required" />
                <Container className="my-5">
                    <Alert variant="warning" className="text-center">
                        <i className="bi bi-exclamation-triangle-fill me-2"></i>
                        You must be logged in to complete your checkout.
                    </Alert>
                    <div className="text-center mt-4">
                        <Button
                            variant="primary"
                            onClick={() => router.visit('/login', {
                                data: { redirect: window.location.pathname }
                            })}
                            className="px-4 py-2 rounded-pill"
                        >
                            <FiLogIn className="me-2" />
                            Login to Continue
                        </Button>
                    </div>
                </Container>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Checkout" />
            <Container className="checkout-page my-5">
                <Row className="justify-content-center">
                    <Col>
                        <div className="d-flex justify-content-between align-items-center mb-4">
                            <h2 className="mb-0 fw-bold">Complete Your Subscription</h2>
                            <Button
                                variant="outline-primary"
                                size="sm"
                                onClick={() => router.visit(route('plan.index'))}
                                className="rounded-pill"
                            >
                                <FiArrowLeft className="me-1" />
                                Change Plan
                            </Button>
                        </div>

                        <hr className='dashed-hr mb-4' />

                        {error && (
                            <Alert variant="danger" className="mb-4 d-flex align-items-center">
                                <i className="bi bi-exclamation-triangle-fill me-2"></i>
                                {error}
                            </Alert>
                        )}

                        {success && (
                            <Alert variant="success" className="mb-4 d-flex align-items-center">
                                <i className="bi bi-check-circle-fill me-2"></i>
                                {success}
                            </Alert>
                        )}

                        <Row className="g-4">
                            <Col md={7}>
                                <div className="auth-container p-5">
                                    <h5 className="mb-0 fw-semibold d-flex align-items-center mb-3">
                                        M-Pesa Payment
                                    </h5>
                                    <div className="alert alert-info mb-3">
                                        You will receive an M-Pesa push notification to complete payment
                                    </div>

                                    <Form onSubmit={handleSubmit}>
                                        <Form.Group className="mb-4">
                                            <Form.Label className="fw-medium d-flex align-items-center">
                                                <FiPhone className="me-2" />
                                                M-Pesa Phone Number
                                            </Form.Label>
                                            <div className="input-group">
                                                <span className="input-group-text bg-light">+254</span>
                                                <Form.Control
                                                    type="tel"
                                                    name="phone"
                                                    value={formData.phone}
                                                    onChange={(e) => {
                                                        const value = e.target.value.replace(/\D/g, '');
                                                        setFormData(prev => ({
                                                            ...prev,
                                                            phone: value
                                                        }));
                                                    }}
                                                    placeholder="712 345 678"
                                                    maxLength="9"
                                                    required
                                                    className="py-2"
                                                />
                                            </div>
                                            <Form.Text className="text-white-50">
                                                Format: 7XX XXX XXX (without leading 0)
                                            </Form.Text>
                                        </Form.Group>

                                        <div className="d-grid mt-4">
                                            <Button
                                                variant="success"
                                                type="submit"
                                                disabled={processing}
                                                className="submit-button py-3 rounded-pill fw-semibold"
                                            >
                                                {processing ? (
                                                    <>
                                                        <Spinner
                                                            as="span"
                                                            animation="border"
                                                            size="sm"
                                                            role="status"
                                                            aria-hidden="true"
                                                        />
                                                        <span className="ms-2">Processing...</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        Pay Ksh {cart.total} via M-Pesa
                                                    </>
                                                )}
                                            </Button>
                                        </div>

                                        <div className="text-center mt-3">
                                            <small className="text-white-50 d-flex align-items-center justify-content-center">
                                                <FiShield className="me-2" />
                                                Secure M-Pesa payment processed by Safaricom
                                            </small>
                                        </div>
                                    </Form>
                                </div>
                            </Col>

                            <Col md={5}>
                                <div className="auth-container p-5">
                                    <h5 className="mb-0 fw-semibold d-flex align-items-center mb-3">
                                        Order Summary
                                    </h5>
                                    {cart.items.map((item, index) => (
                                        <div key={index} className="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                            <div>
                                                <h6 className="mb-1 fw-semibold">{item.name}</h6>
                                                <small className="text-white-50">{item.billing_period} plan</small>
                                            </div>
                                            <div className="text-end">
                                                <h6 className="mb-1 fw-semibold">Ksh {item.price}</h6>
                                            </div>
                                        </div>
                                    ))}

                                    <div className="d-flex justify-content-between mb-2 pt-2">
                                        <span className="text-white-50">Subtotal</span>
                                        <span>Ksh {cart.total}</span>
                                    </div>
                                    <div className="d-flex justify-content-between mb-2">
                                        <span className="text-white-50">Tax (0%)</span>
                                        <span>Ksh 0</span>
                                    </div>

                                    <div className="d-flex justify-content-between fw-bold mt-3 pt-3 h2 border-top">
                                        <span>Total</span>
                                        <span className="text-success fw-bold">Ksh {cart.total}</span>
                                    </div>
                                </div>
                            </Col>
                        </Row>
                    </Col>
                </Row>
            </Container>
        </AppLayout>
    );
}
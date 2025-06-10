import React from 'react';
import { Form, Button, Alert, Row, Col } from 'react-bootstrap';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Register({ status }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        gender: '',
        searching_for: '',
        day: '',
        month: '',
        year: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    // Generate day options (1-31)
    const days = Array.from({ length: 31 }, (_, i) => i + 1);

    // Generate month options (1-12)
    const months = Array.from({ length: 12 }, (_, i) => i + 1);

    // Generate year options (current year - 100 to current year - 18)
    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 82 }, (_, i) => currentYear - 18 - i);

    return (
        <GuestLayout>
            <Head title="Register" />

            <div className="text-start mb-4">
                <h2 className="fw-bold">Members Register</h2>
            </div>

            {status && (
                <Alert variant="success" className="mb-4" dismissible>
                    {status}
                </Alert>
            )}

            <Form onSubmit={submit} className="mb-3">
                <Row>
                    <Col md={6} className="mb-3">
                        <Form.Group controlId="gender">
                            <Form.Select
                                value={data.gender}
                                onChange={(e) => setData('gender', e.target.value)}
                                isInvalid={!!errors.gender}
                                required
                                className="form-control"
                            >
                                <option value="">I am a:</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </Form.Select>
                            <Form.Control.Feedback type="invalid">
                                {errors.gender}
                            </Form.Control.Feedback>
                        </Form.Group>
                    </Col>

                    <Col md={6} className="mb-3">
                        <Form.Group controlId="searching_for">
                            <Form.Select
                                value={data.searching_for}
                                onChange={(e) => setData('searching_for', e.target.value)}
                                isInvalid={!!errors.searching_for}
                                required
                                className="form-control"
                            >
                                <option value="">Searching for a:</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </Form.Select>
                            <Form.Control.Feedback type="invalid">
                                {errors.searching_for}
                            </Form.Control.Feedback>
                        </Form.Group>
                    </Col>

                    <Col md={6} className="mb-3">
                        <Form.Group controlId="name">
                            <Form.Control
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                isInvalid={!!errors.name}
                                placeholder="Name"
                                required
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.name}
                            </Form.Control.Feedback>
                        </Form.Group>
                    </Col>

                    <Col md={6} className="mb-3">
                        <Form.Group controlId="email">
                            <Form.Control
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                isInvalid={!!errors.email}
                                placeholder="Email"
                                required
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.email}
                            </Form.Control.Feedback>
                        </Form.Group>
                    </Col>

                    <Col md={4} className="mb-3">
                        <Form.Group controlId="day">
                            <Form.Select
                                value={data.day}
                                onChange={(e) => setData('day', e.target.value)}
                                isInvalid={!!errors.day}
                                required
                                className="form-control"
                            >
                                <option value="">Day</option>
                                {days.map((day) => (
                                    <option key={day} value={day}>
                                        {day}
                                    </option>
                                ))}
                            </Form.Select>
                            <Form.Control.Feedback type="invalid">
                                {errors.day}
                            </Form.Control.Feedback>
                        </Form.Group>
                    </Col>

                    <Col md={4} className="mb-3">
                        <Form.Group controlId="month">
                            <Form.Select
                                value={data.month}
                                onChange={(e) => setData('month', e.target.value)}
                                isInvalid={!!errors.month}
                                required
                                className="form-control"
                            >
                                <option value="">Month</option>
                                {months.map((month) => (
                                    <option key={month} value={month}>
                                        {month}
                                    </option>
                                ))}
                            </Form.Select>
                            <Form.Control.Feedback type="invalid">
                                {errors.month}
                            </Form.Control.Feedback>
                        </Form.Group>
                    </Col>

                    <Col md={4} className="mb-3">
                        <Form.Group controlId="year">
                            <Form.Select
                                value={data.year}
                                onChange={(e) => setData('year', e.target.value)}
                                isInvalid={!!errors.year}
                                required
                                className="form-control"
                            >
                                <option value="">Year</option>
                                {years.map((year) => (
                                    <option key={year} value={year}>
                                        {year}
                                    </option>
                                ))}
                            </Form.Select>
                            <Form.Control.Feedback type="invalid">
                                {errors.year}
                            </Form.Control.Feedback>
                        </Form.Group>
                    </Col>

                    {/* Password Field */}
                    <Col md={6} className="mb-3">
                        <Form.Group controlId="password">
                            <Form.Control
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                isInvalid={!!errors.password}
                                placeholder="Password"
                                required
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.password}
                            </Form.Control.Feedback>
                        </Form.Group>
                    </Col>

                    {/* Password Confirmation Field */}
                    <Col md={6} className="mb-3">
                        <Form.Group controlId="password_confirmation">
                            <Form.Control
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                isInvalid={!!errors.password_confirmation}
                                placeholder="Confirm Password"
                                required
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.password_confirmation}
                            </Form.Control.Feedback>
                        </Form.Group>
                    </Col>
                </Row>

                <Button
                    variant="primary"
                    type="submit"
                    disabled={processing}
                    className="submit-button w-100 fw-bold"
                >
                    {processing ? 'Registering...' : 'Register'}
                </Button>
            </Form>

            <div className="text-center text-white-50">
                <p className="mb-2">
                    <small>
                        By continuing, you confirm that you have read and agree to our{' '}
                        <Link href="" className="text-decoration-none fw-medium">
                            Terms & Conditions
                        </Link>
                        ,{' '}
                        <Link href="" className="text-decoration-none fw-medium">
                            Privacy Policy
                        </Link>
                        , and{' '}
                        <Link href="" className="text-decoration-none fw-medium">
                            Cookie Policy
                        </Link>
                    </small>
                </p>
                <span>Already have an account? </span>
                <Link
                    href={route('login')}
                    className="fw-medium text-decoration-none"
                >
                    Login
                </Link>
            </div>
        </GuestLayout>
    );
}
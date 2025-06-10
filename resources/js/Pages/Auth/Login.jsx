import React from 'react';
import { Form, Button, Alert } from 'react-bootstrap';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Login" />

            <div className="text-start mb-4">
                <h2 className="fw-bold">Members Login</h2>
            </div>

            {status && (
                <Alert variant="success" className="mb-4" dismissible>
                    {status}
                </Alert>
            )}

            <Form onSubmit={submit} className="mb-3">
                <Form.Group controlId="email" className="mb-3">
                    <Form.Label>Email</Form.Label>
                    <Form.Control
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        autoComplete="username"
                        autoFocus
                        isInvalid={!!errors.email}
                        placeholder="Enter your email"
                    />
                    <Form.Control.Feedback type="invalid">
                        {errors.email}
                    </Form.Control.Feedback>
                </Form.Group>

                <Form.Group controlId="password" className="mb-4">
                    <div className="d-flex justify-content-between align-items-center mb-2">
                        <Form.Label>Password</Form.Label>
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="text-decoration-none small"
                            >
                                Forgot password?
                            </Link>
                        )}
                    </div>
                    <Form.Control
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="current-password"
                        isInvalid={!!errors.password}
                        placeholder="Enter your password"
                    />
                    <Form.Control.Feedback type="invalid">
                        {errors.password}
                    </Form.Control.Feedback>
                </Form.Group>

                <Button
                    variant="primary"
                    type="submit"
                    disabled={processing}
                    className="submit-button w-100 fw-bold"
                >
                    {processing ? 'Logging in...' : 'Log in'}
                </Button>
            </Form>

            <div className="text-center text-white-50">
                <span>Don't have an account? </span>
                <Link href={route('register')} className="fw-medium text-decoration-none">
                    Register
                </Link>
            </div>
        </GuestLayout>
    );
}
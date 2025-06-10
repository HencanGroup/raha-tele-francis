import React from 'react';
import { Form, Button, Alert } from 'react-bootstrap';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Forgot Password" />

            <div className="text-start mb-4">
                <h2 className="fw-bold">Forgot Password</h2>
            </div>

            <div className="mb-4 text-gray-600">
                Forgot your password? No problem. Just let us know your email
                address and we will email you a password reset link that will
                allow you to choose a new one.
            </div>

            {status && (
                <Alert variant="success" className="mb-4" dismissible>
                    {status}
                </Alert>
            )}

            <Form onSubmit={submit} className="mb-3">
                <Form.Group controlId="email" className="mb-4">
                    <Form.Label>Email</Form.Label>
                    <Form.Control
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        autoComplete="email"
                        autoFocus
                        isInvalid={!!errors.email}
                        placeholder="Enter your email"
                    />
                    <Form.Control.Feedback type="invalid">
                        {errors.email}
                    </Form.Control.Feedback>
                </Form.Group>

                <Button
                    variant="primary"
                    type="submit"
                    disabled={processing}
                    className="submit-button w-100 fw-bold"
                >
                    {processing ? 'Sending...' : 'Email Password Reset Link'}
                </Button>
            </Form>

            <div className="text-center text-white-50">
                <span>Remember your password? </span>
                <Link href={route('login')} className="fw-medium text-decoration-none">
                    Login
                </Link>
            </div>
        </GuestLayout>
    );
}
import React from 'react';
import { Form, Button, Alert } from 'react-bootstrap';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Reset Password" />

            <div className="text-start mb-4">
                <h2 className="fw-bold">Reset Password</h2>
            </div>

            <Form onSubmit={submit} className="mb-3">
                <Form.Group controlId="password" className="mb-3">
                    <Form.Label>Password</Form.Label>
                    <Form.Control
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="new-password"
                        isInvalid={!!errors.password}
                        placeholder="Enter new password"
                        autoFocus
                    />
                    <Form.Control.Feedback type="invalid">
                        {errors.password}
                    </Form.Control.Feedback>
                </Form.Group>

                <Form.Group controlId="password_confirmation" className="mb-4">
                    <Form.Label>Confirm Password</Form.Label>
                    <Form.Control
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        autoComplete="new-password"
                        isInvalid={!!errors.password_confirmation}
                        placeholder="Confirm new password"
                    />
                    <Form.Control.Feedback type="invalid">
                        {errors.password_confirmation}
                    </Form.Control.Feedback>
                </Form.Group>

                <Button
                    variant="primary"
                    type="submit"
                    disabled={processing}
                    className="submit-button w-100 fw-bold"
                >
                    {processing ? 'Resetting...' : 'Reset Password'}
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
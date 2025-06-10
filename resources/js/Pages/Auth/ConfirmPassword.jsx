import React from 'react';
import { Form, Button, Alert } from 'react-bootstrap';
import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Confirm Password" />

            <div className="text-start mb-4">
                <h2 className="fw-bold">Confirm Password</h2>
            </div>

            <div className="mb-4 text-gray-600">
                This is a secure area of the application. Please confirm your
                password before continuing.
            </div>

            <Form onSubmit={submit} className="mb-3">
                <Form.Group controlId="password" className="mb-4">
                    <Form.Label>Password</Form.Label>
                    <Form.Control
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="current-password"
                        isInvalid={!!errors.password}
                        placeholder="Enter your password"
                        autoFocus
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
                    {processing ? 'Confirming...' : 'Confirm'}
                </Button>
            </Form>
        </GuestLayout>
    );
}
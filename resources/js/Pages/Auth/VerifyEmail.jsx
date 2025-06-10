import React from 'react';
import { ButtonGroup, Button, Alert } from 'react-bootstrap';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Email Verification" />

            <div className="text-start mb-4">
                <h2 className="fw-bold">Email Verification</h2>
            </div>

            <div className="mb-4 text-gray-600">
                Thanks for signing up! Before getting started, could you verify
                your email address by clicking on the link we just emailed to
                you? If you didn't receive the email, we will gladly send you
                another.
            </div>

            {status === 'verification-link-sent' && (
                <Alert variant="success" className="mb-4" dismissible>
                    A new verification link has been sent to the email address
                    you provided during registration.
                </Alert>
            )}

            <ButtonGroup className='d-flex gap-2'>
                <Button
                    variant="primary"
                    onClick={submit}
                    disabled={processing}
                    className="submit-button rounded fw-bold"
                >
                    {processing ? 'Sending...' : 'Resend Verification Email'}
                </Button>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="submit-button rounded fw-bold"
                >
                    Log Out
                </Link>
            </ButtonGroup>
        </GuestLayout>
    );
}
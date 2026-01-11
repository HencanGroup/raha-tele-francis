import React from "react";
import { Button, Alert } from "react-bootstrap";
import { Head, Link, useForm } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();
        post(route("verification.send"));
    };

    return (
        <GuestLayout>
            <Head title="Email Verification" />

            <div className="auth-content">
                <div className="text-center mb-4">
                    <h2 className="auth-title fw-bold mb-2">Verify Email</h2>
                    <p className="auth-subtitle text-white-50 mb-0">
                        Complete your registration
                    </p>
                </div>

                <div className="mb-4 text-white-50 text-center">
                    Thanks for signing up! Before getting started, please verify
                    your email address by clicking on the link we just emailed
                    to you. If you didn't receive the email, we'll gladly send
                    you another.
                </div>

                {status === "verification-link-sent" && (
                    <Alert variant="success" className="mb-4 py-2" dismissible>
                        A new verification link has been sent to your email
                        address.
                    </Alert>
                )}

                <div className="d-grid gap-3">
                    <Button
                        variant="gold"
                        onClick={submit}
                        disabled={processing}
                        className="auth-btn py-2 fw-bold"
                    >
                        {processing ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" />
                                Sending...
                            </>
                        ) : (
                            "Resend Verification Email"
                        )}
                    </Button>

                    <Link
                        href={route("logout")}
                        method="post"
                        as="button"
                        className="btn btn-outline-light w-100 py-2"
                    >
                        Log Out
                    </Link>
                </div>
            </div>
        </GuestLayout>
    );
}

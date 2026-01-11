import React from "react";
import { Form, Button, Alert } from "react-bootstrap";
import { Head, Link, useForm } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("password.email"));
    };

    return (
        <GuestLayout>
            <Head title="Forgot Password" />

            <div className="auth-content">
                <div className="text-center mb-4">
                    <h2 className="auth-title fw-bold mb-2">Reset Password</h2>
                    <p className="auth-subtitle text-white-50 mb-0">
                        Enter your email to receive a reset link
                    </p>
                </div>

                <div className="mb-4 text-white-50 text-center">
                    Forgot your password? No problem. Just enter your email
                    address and we'll send you a password reset link.
                </div>

                {status && (
                    <Alert variant="success" className="mb-4 py-2" dismissible>
                        {status}
                    </Alert>
                )}

                <Form onSubmit={submit} className="mb-3">
                    {/* Email */}
                    <Form.Group className="mb-4">
                        <Form.Control
                            type="email"
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                            isInvalid={!!errors.email}
                            placeholder="Enter your email"
                            required
                            autoComplete="email"
                            autoFocus
                            className="auth-input py-2"
                        />
                        <Form.Control.Feedback type="invalid">
                            {errors.email}
                        </Form.Control.Feedback>
                    </Form.Group>

                    {/* Submit Button */}
                    <Button
                        variant="gold"
                        type="submit"
                        disabled={processing}
                        className="w-100 auth-btn py-2 fw-bold mb-3"
                    >
                        {processing ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" />
                                Sending...
                            </>
                        ) : (
                            "Send Reset Link"
                        )}
                    </Button>

                    {/* Divider */}
                    <div className="divider my-3 position-relative text-center">
                        <span className="divider-text bg-dark px-3 small">
                            Remember your password?
                        </span>
                    </div>

                    {/* Login Link */}
                    <div className="text-center">
                        <Link
                            href={route("login")}
                            className="btn btn-outline-light w-100 py-2"
                        >
                            Back to Login
                        </Link>
                    </div>
                </Form>
            </div>
        </GuestLayout>
    );
}

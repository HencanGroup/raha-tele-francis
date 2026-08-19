import React, { useState } from "react";
import { Form, Button, Alert } from "react-bootstrap";
import { Head, Link, router } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";
import SocialButtons from "@/Components/Auth/SocialButtons";
import { login, storeTwoFactorToken, completeAuth } from "@/Utils/auth";

export default function Login({ status, canResetPassword }) {
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [processing, setProcessing] = useState(false);
    const [errorMessage, setErrorMessage] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});

    const submit = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrorMessage(null);
        setFieldErrors({});

        try {
            const data = await login({ email, password });

            // 2FA is enabled — store the temporary token and show the challenge.
            if (data.two_factor_required) {
                storeTwoFactorToken(data.two_factor_token);
                router.visit("/login/two-factor");
                return;
            }

            await completeAuth(data);
        } catch (err) {
            const { status, data } = err?.response || {};

            if (status === 422 && data?.errors) {
                const errors = {};
                Object.entries(data.errors).forEach(([field, messages]) => {
                    errors[field] = Array.isArray(messages) ? messages[0] : messages;
                });
                setFieldErrors(errors);
            } else {
                setErrorMessage(data?.message || "Unable to sign in. Please try again.");
            }

            setProcessing(false);
        }
    };

    return (
        <GuestLayout>
            <Head title="Login" />

            <div className="auth-content">
                <div className="text-center mb-4">
                    <h2 className="auth-title fw-bold mb-2">Welcome Back</h2>
                    <p className="auth-subtitle text-white-50 mb-0">
                        Sign in to your dating account
                    </p>
                </div>

                {status && (
                    <Alert variant="success" className="mb-4 py-2" dismissible>
                        {status}
                    </Alert>
                )}

                {errorMessage && (
                    <Alert variant="danger" className="mb-4 py-2" dismissible onClose={() => setErrorMessage(null)}>
                        {errorMessage}
                    </Alert>
                )}

                <Form onSubmit={submit} className="mb-3">
                    {/* Email */}
                    <Form.Group className="mb-3">
                        <Form.Control
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            isInvalid={!!fieldErrors.email}
                            placeholder="Enter your email"
                            required
                            autoComplete="email"
                            autoFocus
                            className="auth-input py-2"
                        />
                        <Form.Control.Feedback type="invalid">
                            {fieldErrors.email}
                        </Form.Control.Feedback>
                    </Form.Group>

                    {/* Password */}
                    <Form.Group className="mb-4">
                        <Form.Control
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            isInvalid={!!fieldErrors.password}
                            placeholder="Enter your password"
                            required
                            autoComplete="current-password"
                            className="auth-input py-2"
                        />
                        <Form.Control.Feedback type="invalid">
                            {fieldErrors.password}
                        </Form.Control.Feedback>

                        {/* Forgot Password Link */}
                        {canResetPassword && (
                            <div className="text-end mt-2">
                                <Link
                                    href={route("password.request")}
                                    className="text-white-50 small text-decoration-none"
                                >
                                    Forgot password?
                                </Link>
                            </div>
                        )}
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
                                Logging In...
                            </>
                        ) : (
                            "Sign In"
                        )}
                    </Button>

                    {/* Divider */}
                    <div className="divider my-3 position-relative text-center">
                        <span className="divider-text bg-dark px-3 small">
                            New to our community?
                        </span>
                    </div>

                    {/* Register Link */}
                    <div className="text-center">
                        <Link
                            href={route("register")}
                            className="btn btn-outline-light w-100 py-2"
                        >
                            Create Account
                        </Link>
                    </div>
                </Form>

                {/* Social login */}
                <div className="divider my-3 position-relative text-center">
                    <span className="divider-text bg-dark px-3 small">
                        Or continue with
                    </span>
                </div>
                <SocialButtons />
            </div>
        </GuestLayout>
    );
}
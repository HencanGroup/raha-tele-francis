import React from "react";
import { Form, Button, Alert } from "react-bootstrap";
import { Head, Link, useForm } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("login"), {
            onFinish: () => reset("password"),
        });
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

                <Form onSubmit={submit} className="mb-3">
                    {/* Email */}
                    <Form.Group className="mb-3">
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

                    {/* Password */}
                    <Form.Group className="mb-4">
                        <Form.Control
                            type="password"
                            value={data.password}
                            onChange={(e) =>
                                setData("password", e.target.value)
                            }
                            isInvalid={!!errors.password}
                            placeholder="Enter your password"
                            required
                            autoComplete="current-password"
                            className="auth-input py-2"
                        />
                        <Form.Control.Feedback type="invalid">
                            {errors.password}
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
            </div>
        </GuestLayout>
    );
}

import React from "react";
import { Form, Button, Alert } from "react-bootstrap";
import { Head, Link, useForm } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: "",
        password_confirmation: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("password.store"), {
            onFinish: () => reset("password", "password_confirmation"),
        });
    };

    return (
        <GuestLayout>
            <Head title="Reset Password" />

            <div className="auth-content">
                <div className="text-center mb-4">
                    <h2 className="auth-title fw-bold mb-2">New Password</h2>
                    <p className="auth-subtitle text-white-50 mb-0">
                        Create a new password for your account
                    </p>
                </div>

                <Form onSubmit={submit}>
                    {/* Password */}
                    <Form.Group className="mb-3">
                        <Form.Control
                            type="password"
                            value={data.password}
                            onChange={(e) =>
                                setData("password", e.target.value)
                            }
                            isInvalid={!!errors.password}
                            placeholder="Enter new password"
                            required
                            autoComplete="new-password"
                            autoFocus
                            minLength={8}
                            className="auth-input py-2"
                        />
                        <Form.Control.Feedback type="invalid">
                            {errors.password}
                        </Form.Control.Feedback>
                        <Form.Text className="text-white-50 small">
                            Must be at least 8 characters
                        </Form.Text>
                    </Form.Group>

                    {/* Confirm Password */}
                    <Form.Group className="mb-4">
                        <Form.Control
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData("password_confirmation", e.target.value)
                            }
                            isInvalid={!!errors.password_confirmation}
                            placeholder="Confirm new password"
                            required
                            autoComplete="new-password"
                            className="auth-input py-2"
                        />
                        <Form.Control.Feedback type="invalid">
                            {errors.password_confirmation}
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
                                Resetting...
                            </>
                        ) : (
                            "Reset Password"
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

import React from "react";
import { Form, Button, Alert } from "react-bootstrap";
import { Head, useForm } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("password.confirm"), {
            onFinish: () => reset("password"),
        });
    };

    return (
        <GuestLayout>
            <Head title="Confirm Password" />

            <div className="auth-content">
                <div className="text-center mb-4">
                    <h2 className="auth-title fw-bold mb-2">
                        Confirm Password
                    </h2>
                    <p className="auth-subtitle text-white-50 mb-0">
                        Secure area verification required
                    </p>
                </div>

                <div className="mb-4 text-white-50 text-center">
                    This is a secure area of the application. Please confirm
                    your password before continuing.
                </div>

                <Form onSubmit={submit}>
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
                            autoFocus
                            className="auth-input py-2"
                        />
                        <Form.Control.Feedback type="invalid">
                            {errors.password}
                        </Form.Control.Feedback>
                    </Form.Group>

                    {/* Submit Button */}
                    <Button
                        variant="gold"
                        type="submit"
                        disabled={processing}
                        className="w-100 auth-btn py-2 fw-bold"
                    >
                        {processing ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" />
                                Confirming...
                            </>
                        ) : (
                            "Confirm Password"
                        )}
                    </Button>
                </Form>
            </div>
        </GuestLayout>
    );
}

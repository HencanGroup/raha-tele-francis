import React, { useState } from "react";
import { Form, Button, Alert } from "react-bootstrap";
import { Head, Link, router } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";
import { verify2fa, recovery2fa, completeAuth, takeTwoFactorToken } from "@/Utils/auth";

export default function TwoFactorChallenge() {
    const [mode, setMode] = useState("code");
    const [code, setCode] = useState("");
    const [recoveryCode, setRecoveryCode] = useState("");
    const [processing, setProcessing] = useState(false);
    const [errorMessage, setErrorMessage] = useState(null);
    const [fieldErrors, setFieldErrors] = useState({});

    const submit = async (e) => {
        e.preventDefault();
        const twoFactorToken = takeTwoFactorToken();

        if (!twoFactorToken) {
            router.visit(route("login"));
            return;
        }

        setProcessing(true);
        setErrorMessage(null);
        setFieldErrors({});

        try {
            const data =
                mode === "code"
                    ? await verify2fa({ code, twoFactorToken })
                    : await recovery2fa({ recoveryCode, twoFactorToken });

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
                setErrorMessage(data?.message || "Unable to verify. Please try again.");
            }

            setProcessing(false);
        }
    };

    // Discard the pending 2FA token when the user abandons the challenge.
    const clearPendingLogin = () => takeTwoFactorToken();

    return (
        <GuestLayout>
            <Head title="Two-Factor Authentication" />

            <div className="auth-content">
                <div className="text-center mb-4">
                    <h2 className="auth-title fw-bold mb-2">Two-Factor Authentication</h2>
                    <p className="auth-subtitle text-white-50 mb-0">
                        Enter the code from your authenticator app or a recovery code
                    </p>
                </div>

                {errorMessage && (
                    <Alert variant="danger" className="mb-4 py-2" dismissible onClose={() => setErrorMessage(null)}>
                        {errorMessage}
                    </Alert>
                )}

                <Form onSubmit={submit} className="mb-3">
                    {mode === "code" ? (
                        <Form.Group className="mb-4">
                            <Form.Control
                                type="text"
                                inputMode="numeric"
                                maxLength={6}
                                value={code}
                                onChange={(e) => setCode(e.target.value)}
                                isInvalid={!!fieldErrors.code}
                                placeholder="Enter the 6-digit code"
                                required
                                autoComplete="one-time-code"
                                autoFocus
                                className="auth-input py-2 text-center fw-bold fs-4"
                            />
                            <Form.Control.Feedback type="invalid">
                                {fieldErrors.code}
                            </Form.Control.Feedback>
                        </Form.Group>
                    ) : (
                        <Form.Group className="mb-4">
                            <Form.Control
                                type="text"
                                value={recoveryCode}
                                onChange={(e) => setRecoveryCode(e.target.value)}
                                isInvalid={!!fieldErrors.recovery_code}
                                placeholder="Enter a recovery code"
                                required
                                className="auth-input py-2"
                            />
                            <Form.Control.Feedback type="invalid">
                                {fieldErrors.recovery_code}
                            </Form.Control.Feedback>
                        </Form.Group>
                    )}

                    <Button
                        variant="gold"
                        type="submit"
                        disabled={processing}
                        className="w-100 auth-btn py-2 fw-bold mb-3"
                    >
                        {processing ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" />
                                Verifying...
                            </>
                        ) : (
                            "Verify"
                        )}
                    </Button>

                    <div className="text-center">
                        <button
                            type="button"
                            className="btn btn-link text-white-50 text-decoration-none"
                            onClick={() => {
                                setMode(mode === "code" ? "recovery" : "code");
                                setFieldErrors({});
                                setErrorMessage(null);
                            }}
                        >
                            {mode === "code"
                                ? "Use a recovery code"
                                : "Use an authentication app"}
                        </button>
                    </div>

                    <div className="text-center mt-1">
                        <Link
                            href={route("login")}
                            className="btn btn-link text-white-50 text-decoration-none"
                            onClick={clearPendingLogin}
                        >
                            Back to login
                        </Link>
                    </div>
                </Form>
            </div>
        </GuestLayout>
    );
}
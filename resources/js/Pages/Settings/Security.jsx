import React, { useEffect, useState } from "react";
import { Alert, Button, Card, Col, Container, Form, Row, Spinner } from "react-bootstrap";
import { Head, usePage } from "@inertiajs/react";
import { toast } from "react-toastify";
import AppLayout from "@/Layouts/AppLayout";
import {
    ensureSessionToken,
    get2faStatus,
    enable2fa,
    confirm2fa,
    disable2fa,
} from "@/Utils/auth";

export default function Security() {
    const { auth } = usePage().props;
    const [loading, setLoading] = useState(true);
    const [enabled, setEnabled] = useState(false);

    // Enable flow
    const [enablePassword, setEnablePassword] = useState("");
    const [setup, setSetup] = useState(null); // { secret, qr_code_url, recovery_codes }
    const [confirmCode, setConfirmCode] = useState("");
    const [setupError, setSetupError] = useState(null);

    // Disable flow
    const [disablePassword, setDisablePassword] = useState("");
    const [disableCode, setDisableCode] = useState("");
    const [disableError, setDisableError] = useState(null);

    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        let mounted = true;

        (async () => {
            try {
                // Make sure a Sanctum token for the session user exists before calling the authed API.
                await ensureSessionToken(auth.user?.id);
                const { enabled: isEnabled } = await get2faStatus();
                if (mounted) setEnabled(isEnabled);
            } catch {
                if (mounted) toast.error("Unable to load 2FA status.");
            } finally {
                if (mounted) setLoading(false);
            }
        })();

        return () => {
            mounted = false;
        };
    }, []);

    const handleEnable = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setSetupError(null);
        try {
            const data = await enable2fa(enablePassword);
            setSetup(data);
            setEnablePassword("");
        } catch (err) {
            setSetupError(
                err?.response?.data?.message ||
                    err?.response?.data?.errors?.password?.[0] ||
                    "Unable to start 2FA setup."
            );
        } finally {
            setProcessing(false);
        }
    };

    const handleConfirm = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setSetupError(null);
        try {
            await confirm2fa(confirmCode);
            setEnabled(true);
            setSetup(null);
            setConfirmCode("");
            toast.success("Two-factor authentication enabled.");
        } catch (err) {
            setSetupError(
                err?.response?.data?.message ||
                    err?.response?.data?.errors?.code?.[0] ||
                    "Invalid verification code."
            );
        } finally {
            setProcessing(false);
        }
    };

    const handleDisable = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setDisableError(null);
        try {
            await disable2fa({
                password: disablePassword,
                code: disableCode,
            });
            setEnabled(false);
            setDisablePassword("");
            setDisableCode("");
            toast.success("Two-factor authentication disabled.");
        } catch (err) {
            setDisableError(
                err?.response?.data?.message ||
                    err?.response?.data?.errors?.password?.[0] ||
                    err?.response?.data?.errors?.code?.[0] ||
                    "Unable to disable 2FA."
            );
        } finally {
            setProcessing(false);
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <Container className="py-5 text-center">
                    <Spinner animation="border" variant="gold" />
                </Container>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Security Settings" />

            <Container className="py-4 settings-security">
                <Row className="justify-content-center">
                    <Col lg={7} md={9}>
                        <Card className="shadow-sm border-0">
                            <Card.Body className="p-4">
                                <h4 className="fw-bold mb-1">Security</h4>
                                <p className="text-white-50 mb-4">
                                    Two-factor authentication adds an extra
                                    layer of security to your account.
                                </p>

                                {!enabled && !setup && (
                                    <>
                                        <Card className="mb-3 border-info">
                                            <Card.Body>
                                                <h5 className="fw-bold d-flex align-items-center gap-2">
                                                    <span aria-hidden="true">📱</span> How to enable 2FA
                                                </h5>
                                                <p className="text-white-50 mb-3">
                                                    It takes about a minute and only needs your phone.
                                                </p>
                                                <ol className="mb-0 ps-3">
                                                    <li className="mb-2">
                                                        Install an authenticator app on your phone if you
                                                        don't have one — e.g.{" "}
                                                        <strong>Google Authenticator</strong>,{" "}
                                                        <strong>Authy</strong> or{" "}
                                                        <strong>Microsoft Authenticator</strong>.
                                                    </li>
                                                    <li className="mb-2">
                                                        Click{" "}
                                                        <strong>Enable Two-Factor</strong> below and enter
                                                        your current password.
                                                    </li>
                                                    <li className="mb-2">
                                                        Open your authenticator app, tap{" "}
                                                        <strong>+</strong> (Add account), and scan the QR
                                                        code shown on screen — or type the secret key
                                                        manually.
                                                    </li>
                                                    <li className="mb-2">
                                                        Your app will start showing a{" "}
                                                        <strong>6-digit code</strong> that refreshes every
                                                        30 seconds.
                                                    </li>
                                                    <li className="mb-2">
                                                        Type the current 6-digit code (e.g.{" "}
                                                        <code>482913</code>) into the{" "}
                                                        <strong>Enter the 6-digit code</strong> box and
                                                        click <strong>Confirm &amp; Enable</strong>.
                                                    </li>
                                                    <li>
                                                        Done — 2FA is on. Keep your{" "}
                                                        <strong>8 recovery codes</strong> somewhere safe:
                                                        each can be used once to log in if you lose your
                                                        authenticator app.
                                                    </li>
                                                </ol>
                                            </Card.Body>
                                        </Card>

                                        <Card className="mb-3">
                                            <Card.Body>
                                                <h5 className="fw-bold">
                                                    Two-Factor Authentication
                                                </h5>
                                                <p className="text-white-50 mb-2">
                                                    2FA is currently{" "}
                                                    <span className="text-warning fw-semibold">
                                                        disabled
                                                    </span>
                                                    . When enabled, you'll be
                                                    asked for a one-time code
                                                    from your authenticator app
                                                    when you sign in.
                                                </p>

                                                {setupError && (
                                                    <Alert
                                                        variant="danger"
                                                        className="py-2"
                                                        dismissible
                                                        onClose={() =>
                                                            setSetupError(null)
                                                        }
                                                    >
                                                        {setupError}
                                                    </Alert>
                                                )}

                                                <Form
                                                    onSubmit={handleEnable}
                                                    className="mt-3"
                                                >
                                                    <Form.Group className="mb-3">
                                                        <Form.Control
                                                            type="password"
                                                            value={enablePassword}
                                                            onChange={(e) =>
                                                                setEnablePassword(
                                                                    e.target
                                                                        .value
                                                                )
                                                            }
                                                            placeholder="Enter your current password to enable 2FA"
                                                            required
                                                            autoComplete="current-password"
                                                        />
                                                    </Form.Group>
                                                    <Button
                                                        variant="gold"
                                                        type="submit"
                                                        disabled={processing}
                                                    >
                                                        {processing ? (
                                                            <Spinner
                                                                animation="border"
                                                                size="sm"
                                                                className="me-2"
                                                            />
                                                        ) : null}
                                                        Enable Two-Factor
                                                    </Button>
                                                </Form>
                                            </Card.Body>
                                        </Card>
                                    </>
                                )}

                                {setup && (
                                    <Card className="mb-3">
                                        <Card.Body>
                                            <h5 className="fw-bold">
                                                Scan this QR code
                                            </h5>
                                            <p className="text-white-50">
                                                Open your authenticator app and
                                                scan the code, or enter the
                                                secret manually.
                                            </p>

                                            {setupError && (
                                                <Alert
                                                    variant="danger"
                                                    className="py-2"
                                                    dismissible
                                                    onClose={() =>
                                                        setSetupError(null)
                                                    }
                                                >
                                                    {setupError}
                                                </Alert>
                                            )}

                                            <div className="text-center my-3">
                                                <img
                                                    src={setup.qr_code_url}
                                                    alt="2FA QR code"
                                                    className="rounded"
                                                    style={{ maxWidth: "220px" }}
                                                />
                                            </div>

                                            <p className="mb-1 small text-white-50">
                                                Secret key:
                                            </p>
                                            <code className="d-block bg-dark border p-2 rounded mb-3">
                                                {setup.secret}
                                            </code>

                                            <p className="mb-1 small text-white-50">
                                                Recovery codes (save these —
                                                they are shown only once):
                                            </p>
                                            <div className="d-flex flex-wrap gap-2 mb-3">
                                                {setup.recovery_codes.map(
                                                    (code) => (
                                                        <code
                                                            key={code}
                                                            className="bg-dark border p-1 px-2 rounded"
                                                        >
                                                            {code}
                                                        </code>
                                                    )
                                                )}
                                            </div>

                                            <Form
                                                onSubmit={handleConfirm}
                                                className="mt-3"
                                            >
                                                <Form.Group className="mb-3">
                                                    <Form.Control
                                                        type="text"
                                                        inputMode="numeric"
                                                        maxLength={6}
                                                        value={confirmCode}
                                                        onChange={(e) =>
                                                            setConfirmCode(
                                                                e.target.value
                                                            )
                                                        }
                                                        placeholder="Enter the 6-digit code"
                                                        required
                                                        autoComplete="one-time-code"
                                                    />
                                                </Form.Group>
                                                <Button
                                                    variant="gold"
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    {processing ? (
                                                        <Spinner
                                                            animation="border"
                                                            size="sm"
                                                            className="me-2"
                                                        />
                                                    ) : null}
                                                    Confirm & Enable
                                                </Button>
                                            </Form>
                                        </Card.Body>
                                    </Card>
                                )}

                                {enabled && (
                                    <Card className="mb-3">
                                        <Card.Body>
                                            <h5 className="fw-bold">
                                                Two-Factor Authentication
                                            </h5>
                                            <p className="text-white-50 mb-2">
                                                Status:{" "}
                                                <span className="text-success fw-semibold">
                                                    Enabled
                                                </span>
                                                . Your account requires a
                                                one-time code at sign in.
                                            </p>

                                            {disableError && (
                                                <Alert
                                                    variant="danger"
                                                    className="py-2"
                                                    dismissible
                                                    onClose={() =>
                                                        setDisableError(null)
                                                    }
                                                >
                                                    {disableError}
                                                </Alert>
                                            )}

                                            <Form
                                                onSubmit={handleDisable}
                                                className="mt-3"
                                            >
                                                <Row className="g-3">
                                                    <Col md={6}>
                                                        <Form.Control
                                                            type="password"
                                                            value={disablePassword}
                                                            onChange={(e) =>
                                                                setDisablePassword(
                                                                    e.target
                                                                        .value
                                                                )
                                                            }
                                                            placeholder="Current password"
                                                            required
                                                            autoComplete="current-password"
                                                        />
                                                    </Col>
                                                    <Col md={6}>
                                                        <Form.Control
                                                            type="text"
                                                            inputMode="numeric"
                                                            maxLength={6}
                                                            value={disableCode}
                                                            onChange={(e) =>
                                                                setDisableCode(
                                                                    e.target
                                                                        .value
                                                                )
                                                            }
                                                            placeholder="6-digit code"
                                                            required
                                                            autoComplete="one-time-code"
                                                        />
                                                    </Col>
                                                </Row>
                                                <Button
                                                    variant="outline-danger"
                                                    type="submit"
                                                    disabled={processing}
                                                    className="mt-3"
                                                >
                                                    {processing ? (
                                                        <Spinner
                                                            animation="border"
                                                            size="sm"
                                                            className="me-2"
                                                        />
                                                    ) : null}
                                                    Disable Two-Factor
                                                </Button>
                                            </Form>
                                        </Card.Body>
                                    </Card>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Container>
        </AppLayout>
    );
}
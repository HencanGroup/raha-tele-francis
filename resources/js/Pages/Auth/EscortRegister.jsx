import React, { useState } from "react";
import { Form, Button, Alert, Spinner, Row, Col } from "react-bootstrap";
import { Head, Link, usePage } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";
import useData from "@/Hooks/useData";
import { toast } from "react-toastify";
import xios from "@/Utils/xios";

/**
 * Multi-step escort self-registration form. Consumes the public
 * POST /api/escort/register endpoint. On success, stores the Sanctum
 * token and redirects to /dashboard with a pending-verification banner.
 */
export default function EscortRegister() {
    const { escortServices } = usePage().props;
    const { counties, towns, filterTownsByCounty, isLoading: dataLoading } =
        useData();

    const [step, setStep] = useState(1);
    const [processing, setProcessing] = useState(false);
    const [serverError, setServerError] = useState(null);
    const [errors, setErrors] = useState({});

    // Form data across all steps.
    const [data, setData] = useState({
        first_name: "",
        last_name: "",
        email: "",
        password: "",
        password_confirmation: "",
        stage_name: "",
        phone_number: "",
        bio: "",
        county_id: "",
        town_id: "",
        services: [],
        rate_per_hour: "",
        rate_per_night: "",
        incall_available: false,
        outcall_available: false,
    });

    const set = (field, value) => {
        setData((prev) => ({ ...prev, [field]: value }));
        // Clear field error on change.
        if (errors[field]) {
            setErrors((prev) => {
                const next = { ...prev };
                delete next[field];
                return next;
            });
        }
    };

    const toggleService = (service) => {
        setData((prev) => {
            const services = prev.services.includes(service)
                ? prev.services.filter((s) => s !== service)
                : [...prev.services, service];
            return { ...prev, services };
        });
    };

    const handleCountyChange = (countyId) => {
        set("county_id", countyId);
        set("town_id", "");
        filterTownsByCounty(countyId);
    };

    // Step validation — returns true if the current step's required fields pass.
    const validateStep = () => {
        const newErrors = {};

        if (step === 1) {
            if (!data.first_name.trim())
                newErrors.first_name = "First name is required.";
            if (!data.last_name.trim())
                newErrors.last_name = "Last name is required.";
            if (!data.email.trim()) newErrors.email = "Email is required.";
            else if (!/\S+@\S+\.\S+/.test(data.email))
                newErrors.email = "Invalid email format.";
            if (!data.password) newErrors.password = "Password is required.";
            else if (data.password.length < 8)
                newErrors.password = "Password must be at least 8 characters.";
            if (data.password !== data.password_confirmation)
                newErrors.password_confirmation =
                    "Password confirmation does not match.";
        }

        if (step === 2) {
            if (!data.stage_name.trim())
                newErrors.stage_name = "Stage name is required.";
            if (!data.phone_number.trim())
                newErrors.phone_number = "Phone number is required.";
            else if (!/^2547\d{8}$/.test(data.phone_number))
                newErrors.phone_number =
                    "Phone must be in 2547XXXXXXXX format.";
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const nextStep = () => {
        if (validateStep()) setStep((s) => s + 1);
    };

    const prevStep = () => setStep((s) => s - 1);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateStep()) return;

        setProcessing(true);
        setServerError(null);

        try {
            const payload = {
                first_name: data.first_name,
                last_name: data.last_name,
                email: data.email,
                password: data.password,
                password_confirmation: data.password_confirmation,
                stage_name: data.stage_name,
                phone_number: data.phone_number,
            };

            // Only include optional fields if they have values.
            if (data.bio.trim()) payload.bio = data.bio;
            if (data.county_id) payload.county_id = Number(data.county_id);
            if (data.town_id) payload.town_id = Number(data.town_id);
            if (data.services.length > 0) payload.services = data.services;
            if (data.rate_per_hour)
                payload.rate_per_hour = Number(data.rate_per_hour);
            if (data.rate_per_night)
                payload.rate_per_night = Number(data.rate_per_night);
            payload.incall_available = data.incall_available;
            payload.outcall_available = data.outcall_available;

            const { data: res } = await xios.post(
                "/api/escort/register",
                payload
            );

            // Store the Sanctum token + user and redirect to confirmation page.
            const token = res.data.token;
            const user = res.data.user;

            localStorage.setItem("raha_sanctum_token", token);
            localStorage.setItem("raha_user_id", user.id);

            // Redirect to the registration-confirmed page (guest layout).
            window.location.href = "/escort/registration-confirmed";
        } catch (err) {
            if (err?.response?.status === 422) {
                setErrors(err.response.data.errors || {});
                toast.error("Please fix the errors below.");
            } else {
                setServerError(
                    err?.response?.data?.message ||
                        "Registration failed. Please try again."
                );
                toast.error(
                    err?.response?.data?.message ||
                        "Registration failed. Please try again."
                );
            }
        } finally {
            setProcessing(false);
        }
    };

    const stepTitles = [
        "Account Info",
        "Profile",
        "Services & Rates",
        "Review",
    ];

    return (
        <GuestLayout>
            <Head title="Become an Escort" />

            <div className="auth-content">
                {/* Title */}
                <div className="text-center mb-4">
                    <h2 className="auth-title fw-bold mb-2">
                        Become an Escort
                    </h2>
                    <p className="auth-subtitle text-white-50 mb-0">
                        Create your profile and start earning
                    </p>
                </div>

                {/* Step progress indicator */}
                <div className="d-flex justify-content-center gap-2 mb-4">
                    {stepTitles.map((title, i) => (
                        <div
                            key={i}
                            className={`rounded-pill px-3 py-1 small ${
                                i + 1 === step
                                    ? "bg-warning text-dark fw-bold"
                                    : i + 1 < step
                                    ? "bg-success text-white"
                                    : "bg-secondary text-white-50"
                            }`}
                        >
                            {i + 1 < step ? "✓" : i + 1}{" "}
                            <span className="d-none d-md-inline">{title}</span>
                        </div>
                    ))}
                </div>

                {serverError && (
                    <Alert variant="danger" className="mb-3">
                        {serverError}
                    </Alert>
                )}

                {/* Step 1: Account Info */}
                {step === 1 && (
                    <Form>
                        <Form.Group className="mb-3">
                            <Form.Label className="fw-semibold">
                                First Name
                            </Form.Label>
                            <Form.Control
                                type="text"
                                value={data.first_name}
                                onChange={(e) =>
                                    set("first_name", e.target.value)
                                }
                                isInvalid={!!errors.first_name}
                                placeholder="Enter your first name"
                                required
                                className="auth-input py-2"
                                autoFocus
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.first_name}
                            </Form.Control.Feedback>
                        </Form.Group>

                        <Form.Group className="mb-3">
                            <Form.Label className="fw-semibold">
                                Last Name
                            </Form.Label>
                            <Form.Control
                                type="text"
                                value={data.last_name}
                                onChange={(e) =>
                                    set("last_name", e.target.value)
                                }
                                isInvalid={!!errors.last_name}
                                placeholder="Enter your last name"
                                required
                                className="auth-input py-2"
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.last_name}
                            </Form.Control.Feedback>
                        </Form.Group>

                        <Form.Group className="mb-3">
                            <Form.Label className="fw-semibold">
                                Email
                            </Form.Label>
                            <Form.Control
                                type="email"
                                value={data.email}
                                onChange={(e) => set("email", e.target.value)}
                                isInvalid={!!errors.email}
                                placeholder="you@example.com"
                                required
                                className="auth-input py-2"
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.email}
                            </Form.Control.Feedback>
                        </Form.Group>

                        <Row className="g-3">
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label className="fw-semibold">
                                        Password
                                    </Form.Label>
                                    <Form.Control
                                        type="password"
                                        value={data.password}
                                        onChange={(e) =>
                                            set("password", e.target.value)
                                        }
                                        isInvalid={!!errors.password}
                                        placeholder="Min. 8 characters"
                                        required
                                        className="auth-input py-2"
                                    />
                                    <Form.Control.Feedback type="invalid">
                                        {errors.password}
                                    </Form.Control.Feedback>
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label className="fw-semibold">
                                        Confirm Password
                                    </Form.Label>
                                    <Form.Control
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) =>
                                            set(
                                                "password_confirmation",
                                                e.target.value
                                            )
                                        }
                                        isInvalid={
                                            !!errors.password_confirmation
                                        }
                                        placeholder="Re-enter password"
                                        required
                                        className="auth-input py-2"
                                    />
                                    <Form.Control.Feedback type="invalid">
                                        {errors.password_confirmation}
                                    </Form.Control.Feedback>
                                </Form.Group>
                            </Col>
                        </Row>

                        <Button
                            variant="gold"
                            className="w-100 auth-btn py-2 fw-bold mb-3"
                            onClick={nextStep}
                        >
                            Next → Profile
                        </Button>
                    </Form>
                )}

                {/* Step 2: Profile */}
                {step === 2 && (
                    <Form>
                        <Form.Group className="mb-3">
                            <Form.Label className="fw-semibold">
                                Stage Name *
                            </Form.Label>
                            <Form.Control
                                type="text"
                                value={data.stage_name}
                                onChange={(e) =>
                                    set("stage_name", e.target.value)
                                }
                                isInvalid={!!errors.stage_name}
                                placeholder="Your public display name"
                                required
                                className="auth-input py-2"
                                autoFocus
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.stage_name}
                            </Form.Control.Feedback>
                        </Form.Group>

                        <Form.Group className="mb-3">
                            <Form.Label className="fw-semibold">
                                Phone Number *
                            </Form.Label>
                            <Form.Control
                                type="tel"
                                value={data.phone_number}
                                onChange={(e) =>
                                    set("phone_number", e.target.value)
                                }
                                isInvalid={!!errors.phone_number}
                                placeholder="2547XXXXXXXX"
                                pattern="2547\d{8}"
                                required
                                className="auth-input py-2"
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.phone_number}
                            </Form.Control.Feedback>
                            <Form.Text className="text-white-50">
                                Format: 2547XXXXXXXX (12 digits)
                            </Form.Text>
                        </Form.Group>

                        <Form.Group className="mb-3">
                            <Form.Label className="fw-semibold">
                                Bio
                            </Form.Label>
                            <Form.Control
                                as="textarea"
                                rows={3}
                                value={data.bio}
                                onChange={(e) => set("bio", e.target.value)}
                                placeholder="Tell clients about yourself..."
                                className="auth-input"
                                maxLength={5000}
                            />
                            <Form.Text className="text-white-50">
                                {data.bio.length}/5000 characters
                            </Form.Text>
                        </Form.Group>

                        <Row className="g-3">
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label className="fw-semibold">
                                        County
                                    </Form.Label>
                                    <Form.Select
                                        value={data.county_id}
                                        onChange={(e) =>
                                            handleCountyChange(e.target.value)
                                        }
                                        className="auth-input py-2"
                                        disabled={dataLoading}
                                    >
                                        <option value="">Select county</option>
                                        {counties.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label className="fw-semibold">
                                        Town
                                    </Form.Label>
                                    <Form.Select
                                        value={data.town_id}
                                        onChange={(e) =>
                                            set("town_id", e.target.value)
                                        }
                                        className="auth-input py-2"
                                        disabled={
                                            dataLoading || !data.county_id
                                        }
                                    >
                                        <option value="">Select town</option>
                                        {towns.map((t) => (
                                            <option key={t.id} value={t.id}>
                                                {t.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>

                        <div className="d-flex gap-2">
                            <Button
                                variant="outline-secondary"
                                className="flex-grow-1 py-2"
                                onClick={prevStep}
                            >
                                ← Back
                            </Button>
                            <Button
                                variant="gold"
                                className="flex-grow-1 auth-btn py-2 fw-bold"
                                onClick={nextStep}
                            >
                                Next → Services
                            </Button>
                        </div>
                    </Form>
                )}

                {/* Step 3: Services & Rates */}
                {step === 3 && (
                    <Form>
                        <Form.Group className="mb-4">
                            <Form.Label className="fw-semibold">
                                Services Offered
                            </Form.Label>
                            <div
                                className="border border-secondary rounded-3 p-3"
                                style={{ maxHeight: 250, overflowY: "auto" }}
                            >
                                {escortServices?.map((service) => (
                                    <Form.Check
                                        key={service}
                                        type="checkbox"
                                        label={service}
                                        checked={data.services.includes(
                                            service
                                        )}
                                        onChange={() => toggleService(service)}
                                        className="mb-2 text-white"
                                    />
                                ))}
                            </div>
                            <Form.Text className="text-white-50">
                                {data.services.length} service(s) selected
                            </Form.Text>
                        </Form.Group>

                        <Row className="g-3 mb-4">
                            <Col md={6}>
                                <Form.Group>
                                    <Form.Label className="fw-semibold">
                                        Rate per Hour (credits)
                                    </Form.Label>
                                    <Form.Control
                                        type="number"
                                        min="0"
                                        step="100"
                                        value={data.rate_per_hour}
                                        onChange={(e) =>
                                            set("rate_per_hour", e.target.value)
                                        }
                                        placeholder="e.g. 3000"
                                        className="auth-input py-2"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group>
                                    <Form.Label className="fw-semibold">
                                        Rate per Night (credits)
                                    </Form.Label>
                                    <Form.Control
                                        type="number"
                                        min="0"
                                        step="100"
                                        value={data.rate_per_night}
                                        onChange={(e) =>
                                            set(
                                                "rate_per_night",
                                                e.target.value
                                            )
                                        }
                                        placeholder="e.g. 10000"
                                        className="auth-input py-2"
                                    />
                                </Form.Group>
                            </Col>
                        </Row>

                        <div className="d-flex gap-4 mb-4">
                            <Form.Check
                                type="switch"
                                id="incall"
                                label="Incall available"
                                checked={data.incall_available}
                                onChange={(e) =>
                                    set("incall_available", e.target.checked)
                                }
                                className="text-white"
                            />
                            <Form.Check
                                type="switch"
                                id="outcall"
                                label="Outcall available"
                                checked={data.outcall_available}
                                onChange={(e) =>
                                    set("outcall_available", e.target.checked)
                                }
                                className="text-white"
                            />
                        </div>

                        <div className="d-flex gap-2">
                            <Button
                                variant="outline-secondary"
                                className="flex-grow-1 py-2"
                                onClick={prevStep}
                            >
                                ← Back
                            </Button>
                            <Button
                                variant="gold"
                                className="flex-grow-1 auth-btn py-2 fw-bold"
                                onClick={nextStep}
                            >
                                Next → Review
                            </Button>
                        </div>
                    </Form>
                )}

                {/* Step 4: Review & Submit */}
                {step === 4 && (
                    <Form onSubmit={handleSubmit}>
                        <div className="border border-secondary rounded-3 p-3 mb-4">
                            <h6 className="fw-bold mb-3 text-warning">
                                Review Your Details
                            </h6>

                            <Row className="g-3">
                                <Col sm={6}>
                                    <small className="text-white-50 d-block">
                                        Name
                                    </small>
                                    <span>
                                        {data.first_name} {data.last_name}
                                    </span>
                                </Col>
                                <Col sm={6}>
                                    <small className="text-white-50 d-block">
                                        Email
                                    </small>
                                    <span>{data.email}</span>
                                </Col>
                                <Col sm={6}>
                                    <small className="text-white-50 d-block">
                                        Stage Name
                                    </small>
                                    <span>{data.stage_name}</span>
                                </Col>
                                <Col sm={6}>
                                    <small className="text-white-50 d-block">
                                        Phone
                                    </small>
                                    <span>{data.phone_number}</span>
                                </Col>
                                {data.bio && (
                                    <Col sm={12}>
                                        <small className="text-white-50 d-block">
                                            Bio
                                        </small>
                                        <span>{data.bio}</span>
                                    </Col>
                                )}
                                {data.services.length > 0 && (
                                    <Col sm={12}>
                                        <small className="text-white-50 d-block">
                                            Services ({data.services.length})
                                        </small>
                                        <span>
                                            {data.services.join(", ")}
                                        </span>
                                    </Col>
                                )}
                                {data.rate_per_hour && (
                                    <Col sm={6}>
                                        <small className="text-white-50 d-block">
                                            Rate per Hour
                                        </small>
                                        <span>
                                            {Number(
                                                data.rate_per_hour
                                            ).toLocaleString()}{" "}
                                            credits
                                        </span>
                                    </Col>
                                )}
                                {data.rate_per_night && (
                                    <Col sm={6}>
                                        <small className="text-white-50 d-block">
                                            Rate per Night
                                        </small>
                                        <span>
                                            {Number(
                                                data.rate_per_night
                                            ).toLocaleString()}{" "}
                                            credits
                                        </span>
                                    </Col>
                                )}
                                <Col sm={12}>
                                    <small className="text-white-50 d-block">
                                        Availability
                                    </small>
                                    <span>
                                        {data.incall_available
                                            ? "Incall"
                                            : ""}
                                        {data.incall_available &&
                                        data.outcall_available
                                            ? " & "
                                            : ""}
                                        {data.outcall_available
                                            ? "Outcall"
                                            : ""}
                                        {!data.incall_available &&
                                            !data.outcall_available &&
                                            "Not set"}
                                    </span>
                                </Col>
                            </Row>
                        </div>

                        <Alert variant="info" className="mb-4 small">
                            Your profile will be reviewed by our team. You'll
                            be able to log in once approved.
                        </Alert>

                        <div className="d-flex gap-2">
                            <Button
                                variant="outline-secondary"
                                className="flex-grow-1 py-2"
                                onClick={prevStep}
                                type="button"
                            >
                                ← Back
                            </Button>
                            <Button
                                variant="gold"
                                type="submit"
                                className="flex-grow-1 auth-btn py-2 fw-bold"
                                disabled={processing}
                            >
                                {processing ? (
                                    <>
                                        <Spinner
                                            animation="border"
                                            size="sm"
                                            className="me-2"
                                        />
                                        Creating Account...
                                    </>
                                ) : (
                                    "Create Account"
                                )}
                            </Button>
                        </div>
                    </Form>
                )}

                {/* Login link */}
                <div className="text-center mt-3">
                    <small className="text-white-50">
                        Already have an account?{" "}
                        <Link
                            href={route("login")}
                            className="text-warning text-decoration-none"
                        >
                            Log in
                        </Link>
                    </small>
                </div>
            </div>
        </GuestLayout>
    );
}

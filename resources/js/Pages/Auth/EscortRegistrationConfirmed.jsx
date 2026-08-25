import React from "react";
import { Button } from "react-bootstrap";
import { Head, Link } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";

/**
 * Shown after a successful escort self-registration. Informs the applicant
 * that their profile is under review and will be activated once an admin
 * approves it. A confirmation email has been sent to their address.
 */
export default function EscortRegistrationConfirmed() {
    return (
        <GuestLayout>
            <Head title="Registration Received" />

            <div className="auth-content">
                <div className="text-center mb-4">
                    <div
                        className="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3"
                        style={{ width: 80, height: 80, fontSize: 40 }}
                    >
                        ✓
                    </div>
                    <h2 className="auth-title fw-bold mb-2">
                        Application Received!
                    </h2>
                    <p className="auth-subtitle text-white-50 mb-0">
                        Thank you for registering as an escort
                    </p>
                </div>

                <div className="text-center text-white-50 mb-4">
                    <p>
                        We have received your application and it is now{" "}
                        <strong className="text-white">under review</strong> by
                        our team.
                    </p>
                    <p>
                        We will notify you by email once your profile has been
                        approved. This usually takes{" "}
                        <strong className="text-white">24-48 hours</strong>.
                    </p>
                    <p className="mb-0">
                        In the meantime, you can log in to your account, but
                        your profile will not be visible to clients until it is
                        verified.
                    </p>
                </div>

                <div className="d-grid gap-3">
                    <Link
                        href={route("login")}
                        className="btn btn-gold auth-btn py-2 fw-bold"
                    >
                        Log In to Your Account
                    </Link>

                    <Link
                        href="/"
                        className="btn btn-outline-light py-2"
                    >
                        Back to Home
                    </Link>
                </div>
            </div>
        </GuestLayout>
    );
}

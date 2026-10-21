import { Head, Link } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";

export default function Terms() {
    return (
        <AppLayout>
            <Head title="Terms of Use | Raha Tele" />

            <section className="container my-5 py-4 legal-page">
                <div className="row justify-content-center">
                    <div className="col-lg-8">

                        {/* Pending-legal-text notice — removed once client supplies content (SOW §2.11). */}
                        <div
                            className="
                                alert alert-warning border-gold
                                text-center mb-4
                            "
                        >
                            <strong className="text-gold">
                                Legal content pending.
                            </strong>{" "}
                            Full Terms of Use text to be provided by the client
                            per SOW §2.11 and §4. Developer will insert it here.
                        </div>

                        <div className="card card-legal bg-white text-dark p-4 rounded-3 shadow">
                            <div className="text-center mb-4">
                                <h1 className="fw-bold text-gold mb-2">
                                    Terms of Use
                                </h1>
                                <p className="text-muted small">
                                    Last updated: {new Date().getFullYear()}
                                </p>
                            </div>

                            <div className="legal-content text-start">
                                <p className="lead">
                                    Welcome to Raha Tele. These Terms of Use
                                    govern your use of our platform connecting
                                    clients with verified escorts in Kenya.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    1. Acceptance of Terms
                                </h2>
                                <p>
                                    By accessing or using Raha Tele, you agree
                                    to be bound by these Terms of Use, our
                                    <Link href="/privacy">Privacy Policy</Link>,
                                    and all applicable laws and regulations.
                                    If you do not agree, you may not use the
                                    platform.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    2. Eligibility
                                </h2>
                                <p>
                                    You must be at least 18 years of age to use
                                    this platform. Raha Tele is intended for
                                    adults seeking lawful companionship services
                                    only.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    3. Account &amp; Credits
                                </h2>
                                <p>
                                    Users create accounts and purchase credit
                                    bundles to interact with escorts (phone
                                    unlocks, paid messages, conversations).
                                    Credits are non-refundable unless required
                                    by law.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    4. Conduct
                                </h2>
                                <p>
                                    Users must not post unlawful content,
                                    impersonate others, or engage in
                                    harassment. Raha Tele reserves the right to
                                    suspend or ban accounts that violate these
                                    Terms.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    5. Limitation of Liability
                                </h2>
                                <p>
                                    Raha Tele is a introductions platform and is
                                    not a party to any agreement between a
                                    client and an escort. Use of the platform
                                    is at your own risk.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    6. Termination
                                </h2>
                                <p>
                                    We may terminate or suspend your account and
                                    access to the platform at our discretion,
                                    without notice, for conduct we believe
                                    violates these Terms.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    7. Governing Law
                                </h2>
                                <p>
                                    These Terms are governed by the laws of the
                                    Republic of Kenya.
                                </p>

                                <p className="mt-4 text-muted small">
                                    Questions? Contact us at{" "}
                                    <a href="mailto:support@rahatele.co.ke">
                                        support@rahatele.co.ke
                                    </a>
                                    .
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </AppLayout>
    );
}

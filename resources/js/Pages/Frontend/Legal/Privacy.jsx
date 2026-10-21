import { Head, Link } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";

export default function Privacy() {
    return (
        <AppLayout>
            <Head title="Privacy Policy | Raha Tele" />

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
                            Full Privacy Policy text to be provided by the
                            client per SOW §2.11 and §4. Developer will insert
                            it here.
                        </div>

                        <div className="card card-legal bg-white text-dark p-4 rounded-3 shadow">
                            <div className="text-center mb-4">
                                <h1 className="fw-bold text-gold mb-2">
                                    Privacy Policy
                                </h1>
                                <p className="text-muted small">
                                    Last updated: {new Date().getFullYear()}
                                </p>
                            </div>

                            <div className="legal-content text-start">
                                <p className="lead">
                                    Raha Tele respects your privacy. This
                                    Privacy Policy explains what personal
                                    information we collect, how we use it, and
                                    your rights regarding your data.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    1. Information We Collect
                                </h2>
                                <p>
                                    We collect account information (name, email,
                                    phone), profile details, and usage data
                                    necessary to operate the platform and
                                    process credit purchases via M-Pesa.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    2. How We Use Your Information
                                </h2>
                                <p>
                                    To provide and personalize services, process
                                    payments, send service-related
                                    communications, and improve the platform.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    3. Data Sharing
                                </h2>
                                <p>
                                    We do not sell your personal information. We
                                    may share data with payment processors
                                    (M-Pesa/Daraja) and service providers as
                                    required to operate the platform.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    4. Your Rights
                                </h2>
                                <p>
                                    You may access, correct, or delete your
                                    personal data by contacting support or
                                    managing your account settings.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    5. Cookies &amp; Tracking
                                </h2>
                                <p>
                                    We use cookies and similar technologies to
                                    authenticate sessions, prevent fraud, and
                                    analyze traffic. You may control cookies
                                    via your browser settings.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    6. Data Security
                                </h2>
                                <p>
                                    We use industry-standard encryption and
                                    access controls to protect your data, but
                                    no internet transmission is 100% secure.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    7. Children
                                </h2>
                                <p>
                                    Raha Tele is for adults 18+. We do not
                                    knowingly collect data from minors.
                                </p>

                                <h2 className="fw-bold text-gold mt-4 mb-2">
                                    8. Changes to This Policy
                                </h2>
                                <p>
                                    We may update this Privacy Policy from time
                                    to time. Continued use constitutes
                                    acceptance of changes.
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

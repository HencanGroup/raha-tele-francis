import { useState } from "react";
import { Alert, Button, Form, Modal } from "react-bootstrap";
import { Flag, Loader } from "lucide-react";
import xios from "@/Utils/xios";
import { useErrorToast } from "@/Hooks/useErrorToast";
import { toast } from "react-toastify";

/* Max report description length — mirrors backend validation (2000 chars). */
const DESCRIPTION_MAX = 2000;

/**
 * Modal for reporting an inappropriate review.
 *
 * Submits POST /api/reviews/{review}/report with a required reason and
 * optional description. Creates a pending Report row handled by admins
 * in the Filament ReportResource.
 */
const ReportReviewModal = ({ show, review, onHide }) => {
    const { showErrorToast } = useErrorToast();

    /* ---------------- FORM STATE ---------------- */
    const [reason, setReason] = useState("");
    const [description, setDescription] = useState("");
    const [submitting, setSubmitting] = useState(false);

    /* ---------------- ACTIONS ---------------- */
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!review) return;
        if (!reason.trim()) {
            toast.error("Please provide a reason for the report.");
            return;
        }

        setSubmitting(true);

        try {
            await xios.post(`/api/reviews/${review.id}/report`, {
                reason: reason.trim(),
                description: description.trim() || undefined,
            });

            toast.success("Review reported. Our team will review it shortly.");
            setReason("");
            setDescription("");
            onHide();
        } catch (error) {
            showErrorToast(error);
        } finally {
            setSubmitting(false);
        }
    };

    const handleClose = () => {
        if (submitting) return;
        setReason("");
        setDescription("");
        onHide();
    };

    /* ---------------- RENDER ---------------- */
    return (
        <Modal show={show} onHide={handleClose} centered>
            <Modal.Header closeButton className="border-secondary">
                <Modal.Title className="d-flex align-items-center gap-2">
                    <Flag size={18} className="text-warning" />
                    Report Review
                </Modal.Title>
            </Modal.Header>

            <Form onSubmit={handleSubmit}>
                <Modal.Body>
                    <Alert variant="warning" className="mb-3">
                        Reporting is only for inappropriate or abusive reviews.
                        False reports may be penalised.
                    </Alert>

                    <Form.Group className="mb-3">
                        <Form.Label>Reason</Form.Label>
                        <Form.Control
                            as="select"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            className="bg-dark text-white border-secondary"
                        >
                            <option value="">Select a reason...</option>
                            <option value="Inappropriate content">Inappropriate content</option>
                            <option value="Spam or fake review">Spam or fake review</option>
                            <option value="Hate speech or harassment">Hate speech or harassment</option>
                            <option value="False information">False information</option>
                            <option value="Other">Other</option>
                        </Form.Control>
                    </Form.Group>

                    <Form.Group className="mb-2">
                        <div className="d-flex justify-content-between">
                            <Form.Label>Description (optional)</Form.Label>
                            <small className="text-white-50">
                                {description.length}/{DESCRIPTION_MAX}
                            </small>
                        </div>
                        <Form.Control
                            as="textarea"
                            rows={3}
                            value={description}
                            maxLength={DESCRIPTION_MAX}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Provide more details about the issue..."
                            className="bg-transparent text-white border-secondary"
                            style={{ resize: "none" }}
                        />
                    </Form.Group>
                </Modal.Body>

                <Modal.Footer className="border-secondary">
                    <Button
                        variant="outline-light"
                        onClick={handleClose}
                        disabled={submitting}
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="danger"
                        type="submit"
                        disabled={submitting}
                        className="d-flex align-items-center gap-2"
                    >
                        {submitting ? (
                            <>
                                <Loader className="spinner-border-sm" />
                                Submitting...
                            </>
                        ) : (
                            <>
                                <Flag size={16} />
                                Submit Report
                            </>
                        )}
                    </Button>
                </Modal.Footer>
            </Form>
        </Modal>
    );
};

export default ReportReviewModal;
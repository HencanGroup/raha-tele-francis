import { useState } from "react";
import { Button, Form } from "react-bootstrap";
import { FaStar } from "react-icons/fa";
import { Star, Loader, Send } from "lucide-react";
import xios from "@/Utils/xios";
import { useErrorToast } from "@/Hooks/useErrorToast";
import { toast } from "react-toastify";

/* Max review comment length — mirrors backend validation (1000 chars). */
const COMMENT_MAX = 1000;

/**
 * Write / edit review form for an escort profile.
 *
 * Create mode posts to POST /api/reviews; edit mode (initialReview set)
 * updates via PUT /api/reviews/{id}. Both flows go through the Sanctum
 * API via `xios` so the session user's Bearer token is attached.
 */
const ReviewForm = ({ escortId, initialReview = null, onCancel, onSubmitted }) => {
    const { showErrorToast } = useErrorToast();

    const isEditing = Boolean(initialReview);

    /* ---------------- FORM STATE ---------------- */
    const [rating, setRating] = useState(initialReview?.rating || 0);
    const [comment, setComment] = useState(initialReview?.comment || "");
    const [submitting, setSubmitting] = useState(false);

    /* ---------------- ACTIONS ---------------- */
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (rating < 1) {
            toast.error("Please select a rating.");
            return;
        }
        if (!comment.trim()) {
            toast.error("Please write a comment.");
            return;
        }

        setSubmitting(true);

        try {
            const payload = isEditing
                ? { rating, comment: comment.trim() }
                : { escort_id: escortId, rating, comment: comment.trim() };

            const response = await xios[isEditing ? "put" : "post"](
                isEditing ? `/api/reviews/${initialReview.id}` : "/api/reviews",
                payload,
            );

            toast.success(
                isEditing
                    ? "Your review has been updated."
                    : "Thank you! Your review has been submitted.",
            );
            onSubmitted(response.data.data);
        } catch (error) {
            // Handles 422 validation errors, 409 duplicate review, and 401/403.
            showErrorToast(error);
        } finally {
            setSubmitting(false);
        }
    };

    /* ---------------- RENDER ---------------- */
    return (
        <Form onSubmit={handleSubmit} className="p-3 rounded-3 bg-white bg-opacity-10 border border-secondary">
            <div className="d-flex justify-content-between align-items-center mb-2">
                <h6 className="mb-0 text-white">
                    {isEditing ? "Edit Your Review" : "Write a Review"}
                </h6>
                <small className="text-white-50">
                    {comment.length}/{COMMENT_MAX}
                </small>
            </div>

            {/* Interactive star picker */}
            <div className="d-flex align-items-center gap-1 mb-3">
                {[1, 2, 3, 4, 5].map((star) => (
                    <Button
                        key={star}
                        variant="link"
                        type="button"
                        className={`p-0 border-0 ${star <= rating ? "text-warning" : "text-white-50"}`}
                        onClick={() => setRating(star)}
                        title={`${star} star${star > 1 ? "s" : ""}`}
                    >
                        <FaStar size={24} />
                    </Button>
                ))}
            </div>

            {/* Comment input */}
            <Form.Control
                as="textarea"
                rows={4}
                value={comment}
                maxLength={COMMENT_MAX}
                onChange={(e) => setComment(e.target.value)}
                placeholder="Share your experience with this escort..."
                className="bg-transparent text-white border-secondary mb-3"
                style={{ resize: "none" }}
            />

            <div className="d-flex gap-2 justify-content-end">
                {isEditing && (
                    <Button
                        variant="outline-light"
                        onClick={onCancel}
                        disabled={submitting}
                        type="button"
                    >
                        Cancel
                    </Button>
                )}
                <Button
                    variant="gold"
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
                            <Send size={16} />
                            {isEditing ? "Update Review" : "Submit Review"}
                        </>
                    )}
                </Button>
            </div>
        </Form>
    );
};

export default ReviewForm;
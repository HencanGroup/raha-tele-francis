import { useState, useEffect, useCallback, useMemo } from "react";
import { usePage, router } from "@inertiajs/react";
import { Badge, Button, Card, ProgressBar, Spinner } from "react-bootstrap";
import { CheckCircle, Star, Pencil, Trash2, Flag, MessageSquare } from "lucide-react";
import { FaStar } from "react-icons/fa";
import { toast } from "react-toastify";
import xios from "@/Utils/xios";
import { useErrorToast } from "@/Hooks/useErrorToast";
import ReviewForm from "./ReviewForm";
import ReportReviewModal from "./ReportReviewModal";

/* Number of reviews shown before the "Show All" toggle kicks in. */
const PREVIEW_COUNT = 3;

/**
 * Reviews section for the escort profile (Frontend/Escort/Show).
 *
 * Fetches the visible+verified review list from GET /api/escorts/{id}/reviews
 * and handles writing, editing, deleting, and reporting reviews through the
 * Sanctum API. The header aggregate (rating / review_count) is refreshed via
 * a partial Inertia reload after every mutation since the backend recomputes
 * it through Escort::updateRating().
 */
const ReviewsSection = ({ escortId, rating, reviewCount }) => {
    const { auth } = usePage().props;
    const { showErrorToast } = useErrorToast();

    const user = auth?.user;
    const isMember = user?.user_type === "member";

    /* ---------------- STATE ---------------- */
    const [reviews, setReviews] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showAll, setShowAll] = useState(false);
    const [showForm, setShowForm] = useState(false);
    const [editingReview, setEditingReview] = useState(null);
    const [reportReview, setReportReview] = useState(null);
    const [hasReviewed, setHasReviewed] = useState(false);

    /* ---------------- DATA ---------------- */
    const loadReviews = useCallback(async () => {
        setLoading(true);

        try {
            // Public endpoint — returns visible + verified reviews only.
            const response = await xios.get(`/api/escorts/${escortId}/reviews`, {
                params: { per_page: 50 },
            });
            setReviews(response.data.data || []);
            setHasReviewed(response.data.meta?.has_reviewed ?? false);
        } catch (error) {
            showErrorToast(error);
        } finally {
            setLoading(false);
        }
    }, [escortId, showErrorToast]);

    useEffect(() => {
        loadReviews();
    }, [loadReviews]);

    // Rating distribution for the header bars, derived from loaded reviews.
    const distribution = useMemo(() => {
        const counts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };

        reviews.forEach((review) => {
            const star = Math.round(review.rating);
            if (counts[star] !== undefined) counts[star] += 1;
        });

        const total = reviews.length || 1;

        return [5, 4, 3, 2, 1].map((star) => ({
            star,
            percent: Math.round((counts[star] / total) * 100),
        }));
    }, [reviews]);

    const visibleReviews = showAll ? reviews : reviews.slice(0, PREVIEW_COUNT);

    /* ---------------- HELPERS ---------------- */
    const renderStars = (value) => {
        return Array.from({ length: 5 }).map((_, index) => (
            <span key={index} className="text-warning">
                {index < Math.floor(value || 0) ? (
                    <FaStar size={16} />
                ) : (
                    <Star size={16} />
                )}
            </span>
        ));
    };

    const isOwnReview = (review) => review.author?.id === user?.id;

    // Partial reload refreshes the `escort` prop so the aggregate rating and
    // review_count in the header reflect the backend's recomputed values.
    const refreshAggregate = () => {
        router.reload({ only: ["escort"] });
    };

    /* ---------------- MUTATIONS ---------------- */
    const handleSubmitted = (review) => {
        setReviews((prev) => {
            const exists = prev.some((r) => r.id === review.id);
            return exists
                ? prev.map((r) => (r.id === review.id ? review : r))
                : [review, ...prev];
        });
        setShowForm(false);
        setEditingReview(null);
        setHasReviewed(true);
        refreshAggregate();
    };

    const handleDelete = async (review) => {
        if (!window.confirm("Delete your review? This cannot be undone.")) {
            return;
        }

        try {
            await xios.delete(`/api/reviews/${review.id}`);
            toast.success("Your review has been deleted.");
            setReviews((prev) => prev.filter((r) => r.id !== review.id));
            setHasReviewed(false);
            refreshAggregate();
        } catch (error) {
            showErrorToast(error);
        }
    };

    /* ---------------- RENDER ---------------- */
    return (
        <div className="">
            {/* Header — aggregate rating + write CTA */}
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 className="mb-0">Customer Reviews</h4>
                    <div className="d-flex align-items-center mt-2">
                        <div className="me-3">
                            <h2 className="text-gold mb-0">{rating || "N/A"}</h2>
                            <div>{renderStars(rating)}</div>
                            <small className="text-white-50">
                                Based on {reviewCount || 0} reviews
                            </small>
                        </div>
                        <div className="ms-4">
                            {distribution.map(({ star, percent }) => (
                                <div
                                    key={star}
                                    className="d-flex align-items-center mb-1"
                                >
                                    <small className="me-2">{star} star</small>
                                    <ProgressBar
                                        now={percent}
                                        className="flex-grow-1"
                                        style={{ height: "8px" }}
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {!showForm && !editingReview && (
                    isMember && !hasReviewed ? (
                        <Button variant="gold" onClick={() => setShowForm(true)}>
                            Write a Review
                        </Button>
                    ) : !user && (
                        <Button
                            variant="outline-gold"
                            onClick={() => router.visit(route("login"))}
                        >
                            Login to Write a Review
                        </Button>
                    )
                )}
            </div>

            {/* Write / edit form */}
            {showForm && (
                <div className="mb-4">
                    <ReviewForm
                        escortId={escortId}
                        onCancel={() => setShowForm(false)}
                        onSubmitted={handleSubmitted}
                    />
                </div>
            )}

            {editingReview && (
                <div className="mb-4">
                    <ReviewForm
                        escortId={escortId}
                        initialReview={editingReview}
                        onCancel={() => setEditingReview(null)}
                        onSubmitted={handleSubmitted}
                    />
                </div>
            )}

            {/* Loading state */}
            {loading && (
                <div className="text-center py-5">
                    <Spinner animation="border" variant="warning" />
                </div>
            )}

            {/* Empty state */}
            {!loading && reviews.length === 0 && (
                <div className="text-center py-5 text-white-50">
                    <MessageSquare size={48} className="mb-2" />
                    <h6>No reviews yet</h6>
                    <p className="mb-0">Be the first to review this escort.</p>
                </div>
            )}

            {/* Review list */}
            {!loading &&
                reviews.length > 0 &&
                visibleReviews.map((review) => (
                    <Card key={review.id} className="mb-3">
                        <Card.Body>
                            <div className="d-flex justify-content-between mb-2">
                                <div>
                                    <strong>
                                        {review.author?.display_name ||
                                            review.author?.first_name ||
                                            "Anonymous"}
                                    </strong>{" "}
                                    {review.is_verified && (
                                        <Badge bg="success" className="ms-2">
                                            <CheckCircle
                                                size={12}
                                                className="me-1"
                                            />
                                            Verified
                                        </Badge>
                                    )}
                                </div>
                                <small className="text-white-50">
                                    {review.created_at_human}
                                </small>
                            </div>

                            <div className="mb-2 d-flex justify-content-between align-items-start">
                                <div>{renderStars(review.rating)}</div>

                                <div className="d-flex align-items-center gap-2">
                                    {isOwnReview(review) && (
                                        <>
                                            <Button
                                                variant="link"
                                                size="sm"
                                                className="p-0 text-white-50"
                                                title="Edit review"
                                                onClick={() => {
                                                    setShowForm(false);
                                                    setEditingReview(review);
                                                }}
                                            >
                                                <Pencil size={16} />
                                            </Button>
                                            <Button
                                                variant="link"
                                                size="sm"
                                                className="p-0 text-white-50"
                                                title="Delete review"
                                                onClick={() =>
                                                    handleDelete(review)
                                                }
                                            >
                                                <Trash2 size={16} />
                                            </Button>
                                        </>
                                    )}

                                    {user && (
                                        <Button
                                            variant="link"
                                            size="sm"
                                            className="p-0 text-white-50"
                                            title="Report review"
                                            onClick={() =>
                                                setReportReview(review)
                                            }
                                        >
                                            <Flag size={16} />
                                        </Button>
                                    )}
                                </div>
                            </div>

                            <p className="mb-0">{review.comment}</p>
                        </Card.Body>
                    </Card>
                ))}

            {/* Show all / less toggle */}
            {!loading && reviews.length > PREVIEW_COUNT && (
                <div className="text-center mt-3">
                    <Button
                        variant="outline-gold"
                        onClick={() => setShowAll(!showAll)}
                    >
                        {showAll
                            ? "Show Less"
                            : `Show All ${reviews.length} Reviews`}
                    </Button>
                </div>
            )}

            {/* Report modal */}
            <ReportReviewModal
                show={Boolean(reportReview)}
                review={reportReview}
                onHide={() => setReportReview(null)}
            />
        </div>
    );
};

export default ReviewsSection;
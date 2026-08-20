import AppLayout from "@/Layouts/AppLayout";
import { Head, Link } from "@inertiajs/react";
import { Container, Row, Col, Card, Image, ListGroup } from "react-bootstrap";
import { Heart, ArrowLeft } from "lucide-react";
import { getProfileImage } from "@/Utils/helpers";

/**
 * Favorites listing — members see the escorts they saved; escorts see the
 * members who favorited them. Reached from the dashboard favorites widget.
 */
const Favorites = ({ favorites, isMember }) => {
    return (
        <AppLayout>
            <Head title="Favorites" />

            <Container className="py-4">
                <div className="d-flex align-items-center justify-content-between mb-4">
                    <h1 className="mb-0">
                        <Heart className="me-2 text-danger" size={26} />
                        Favorites
                    </h1>
                    <Link
                        href="/dashboard"
                        className="btn btn-outline-secondary rounded d-inline-flex align-items-center gap-2"
                    >
                        <ArrowLeft size={16} />
                        Dashboard
                    </Link>
                </div>

                {favorites.length === 0 ? (
                    <Card className="border-0 shadow-sm text-center py-5">
                        <Card.Body>
                            <i className="bi bi-heart fs-1 text-white-50 d-block mb-3"></i>
                            <h5 className="text-white-50">
                                {isMember
                                    ? "You haven't saved any escorts yet"
                                    : "No members have favorited you yet"}
                            </h5>
                        </Card.Body>
                    </Card>
                ) : (
                    <ListGroup variant="flush">
                        {favorites.map((favorite) => (
                            <ListGroup.Item
                                key={favorite.id}
                                className="bg-transparent border-0 py-2"
                            >
                                <Card className="border-0 shadow-sm rounded-3">
                                    <Card.Body className="d-flex align-items-center p-3">
                                        <Image
                                            src={getProfileImage(favorite)}
                                            roundedCircle
                                            width={52}
                                            height={52}
                                            className="object-fit-cover border border-secondary"
                                        />
                                        <div className="ms-3 flex-grow-1 min-w-0">
                                            <h6 className="mb-0 text-truncate">
                                                {favorite.display_name ||
                                                    favorite.name}
                                            </h6>
                                            {favorite.location && (
                                                <small className="text-white-50 d-block">
                                                    <i className="bi bi-geo-alt me-1"></i>
                                                    {favorite.location}
                                                </small>
                                            )}
                                        </div>
                                        <Link
                                            href={
                                                isMember
                                                    ? route("escort.show", favorite.id)
                                                    : route("member.show", favorite.id)
                                            }
                                            className="btn btn-gold rounded px-3"
                                        >
                                            View
                                        </Link>
                                    </Card.Body>
                                </Card>
                            </ListGroup.Item>
                        ))}
                    </ListGroup>
                )}
            </Container>
        </AppLayout>
    );
};

export default Favorites;
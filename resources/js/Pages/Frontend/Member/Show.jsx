import AppLayout from "@/Layouts/AppLayout";
import { Head, Link } from "@inertiajs/react";
import { Container, Row, Col, Card, Image, Badge } from "react-bootstrap";
import { MapPin, CalendarDays, User as UserIcon, ArrowLeft } from "lucide-react";
import dayjs from "dayjs";
import { getProfileImage } from "@/Utils/helpers";

/**
 * Public member profile page — reached from a chat header so an escort can
 * see who they are talking to.
 */
const MemberShow = ({ member }) => {
    const { user } = member;

    return (
        <AppLayout>
            <Head title={user.display_name || user.name} />

            <Container className="py-5" style={{ maxWidth: "720px" }}>
                <Link
                    href="/chat"
                    className="btn btn-outline-secondary rounded mb-4 d-inline-flex align-items-center gap-2"
                >
                    <ArrowLeft size={16} />
                    Back to chats
                </Link>

                <Card className="border-0 shadow-sm rounded-4 overflow-hidden">
                    <div
                        className="p-4"
                        style={{
                            minHeight: "120px",
                            backgroundColor:
                                "rgba(var(--gold-theme) / 0.85)",
                        }}
                    />

                    <Card.Body className="p-4">
                        <div className="text-center mt-n5">
                            <Image
                                src={getProfileImage(user)}
                                roundedCircle
                                width={110}
                                height={110}
                                className="border border-4 border-white shadow-sm object-fit-cover"
                            />
                            <h4 className="fw-semibold mb-0 mt-3">
                                {user.display_name || user.name}
                            </h4>
                            <Badge bg="warning" className="text-dark mt-2">
                                <UserIcon size={12} className="me-1" />
                                Member
                            </Badge>
                        </div>

                        <hr className="my-4" />

                        <Row className="g-3">
                            <Col sm={6}>
                                <Card className="h-100 border-0 bg-light rounded-3 p-3">
                                    <small className="text-muted d-flex align-items-center gap-1">
                                        <MapPin size={14} />
                                        Location
                                    </small>
                                    <strong className="mt-1">
                                        {[user.town, user.county]
                                            .filter(Boolean)
                                            .join(", ") || "Kenya"}
                                    </strong>
                                </Card>
                            </Col>
                            <Col sm={6}>
                                <Card className="h-100 border-0 bg-light rounded-3 p-3">
                                    <small className="text-muted d-flex align-items-center gap-1">
                                        <CalendarDays size={14} />
                                        Member since
                                    </small>
                                    <strong className="mt-1">
                                        {dayjs(user.member_since).format(
                                            "MMMM YYYY",
                                        )}
                                    </strong>
                                </Card>
                            </Col>
                        </Row>
                    </Card.Body>
                </Card>
            </Container>
        </AppLayout>
    );
};

export default MemberShow;
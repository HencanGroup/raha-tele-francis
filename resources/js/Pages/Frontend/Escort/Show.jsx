import AppLayout from "@/Layouts/AppLayout";
import { Head, Link } from "@inertiajs/react";
import { useState } from "react";
import {
    Container,
    Row,
    Col,
    Button,
    Badge,
    Card,
    Image,
    ListGroup,
    ProgressBar,
    Alert,
    ButtonGroup,
    Tabs,
    Tab,
} from "react-bootstrap";
import {
    Star,
    Phone,
    ShieldCheck,
    MapPin,
    Clock,
    Award,
    CheckCircle,
    Image as ImageIcon,
    Globe,
    Navigation,
    Share,
    Eye,
    Users,
    Play,
    Calendar as CalendarIcon,
    UserCheck,
    Shield,
    MessageCircle,
    FileImage,
} from "lucide-react";
import { FaInfoCircle, FaStar } from "react-icons/fa";
import CallModal from "@/Components/Modals/CallModal";
import GalleryModal from "@/Components/Modals/GalleryModal";
import { getProfileImage } from "@/Utils/helpers";
import { toast } from "react-toastify";
import StartChartBtn from "@/Components/ui/StartChartBtn";
import FavoriteButton from "@/Components/Buttons/FavoriteButton";

const EscortShow = ({ escort }) => {
    const { user, resources, reviews, primaryPhoto, is_favorited } = escort;

    const [activeTab, setActiveTab] = useState("overview");
    const [showCallModal, setShowCallModal] = useState(false);
    const [showGalleryModal, setShowGalleryModal] = useState(false);
    const [showAllReviews, setShowAllReviews] = useState(false);

    const galleryImages = resources
        .filter((resource) => resource.type === "image")
        .map((resource) => resource);

    const services = Array.isArray(escort?.services)
        ? escort.services
        : typeof escort?.services === "string"
          ? JSON.parse(escort.services)
          : [
                "Dinner Date",
                "Social Companion",
                "Overnight Stay",
                "Travel Companion",
            ];

    const languages = Array.isArray(escort?.languages)
        ? escort.languages
        : typeof escort?.languages === "string"
          ? JSON.parse(escort.languages)
          : ["English", "Spanish", "French"];

    const special_features = Array.isArray(escort?.special_features)
        ? escort.special_features
        : typeof escort?.special_features === "string"
          ? JSON.parse(escort.special_features)
          : ["English", "Spanish", "French"];

    const attributes = [
        { name: "Height", value: escort?.height },
        { name: "Weight", value: escort?.weight },
        { name: "Hair Color", value: escort?.hair_color },
        { name: "Eye Color", value: escort?.eye_color },
        { name: "Body Type", value: escort?.body_type },
    ];

    const handleShare = () => {
        if (navigator.share) {
            navigator.share({
                title: user?.name,
                text: `Check out ${user?.name} on our platform`,
                url: window.location.href,
            });
        } else {
            navigator.clipboard.writeText(window.location.href);
            toast.alert("Link copied to clipboard!");
        }
    };

    const renderStars = (rating) => {
        return Array.from({ length: 5 }).map((_, index) => (
            <span key={index} className="text-warning">
                {index < Math.floor(rating || 0) ? (
                    <FaStar size={16} />
                ) : (
                    <Star size={16} />
                )}
            </span>
        ));
    };

    return (
        <AppLayout>
            <Head title={`${user?.name || "Escort"} | Premium Companion`} />

            {/* Hero Section */}
            <Card className="rounded-0 border-top-0 border-end-0 border-start-0">
                <Card.Body>
                    <div className="position-absolute top-0 end-0 p-3 z-2">
                        <FavoriteButton
                            escortId={escort.id}
                            initialIsFavorite={is_favorited}
                            size="md"
                            wrapperClass="me-2 d-inline-block"
                        />
                        <Button
                            variant="outline-light"
                            size="sm"
                            className="rounded-circle"
                            onClick={handleShare}
                        >
                            <Share size={20} />
                        </Button>
                    </div>

                    <Container className="py-5">
                        <Row className="align-items-center">
                            <Col lg={4} className="text-center mb-4 mb-lg-0">
                                <div className="position-relative">
                                    <Image
                                        src={getProfileImage(user)}
                                        roundedCircle
                                        className="border border-4 border-gold shadow-lg"
                                        style={{
                                            width: "250px",
                                            height: "250px",
                                            objectFit: "cover",
                                        }}
                                    />
                                    {escort?.is_verified && (
                                        <Badge
                                            bg="success"
                                            className="position-absolute bottom-0 end-0 p-2 rounded-circle"
                                        >
                                            <ShieldCheck size={20} />
                                        </Badge>
                                    )}
                                </div>
                                <ButtonGroup className="gap-2 mt-3 mb-3">
                                    <StartChartBtn
                                        user={escort.user}
                                        className={"btn-gold rounded"}
                                        displayText={
                                            <>
                                                <MessageCircle
                                                    className="me-1"
                                                    size={18}
                                                />
                                                Start Chat
                                            </>
                                        }
                                    />
                                    <Button
                                        variant="outline-light"
                                        className="rounded"
                                        onClick={() => setShowCallModal(true)}
                                    >
                                        <Phone className="me-1" size={18} />
                                        Call Now
                                    </Button>
                                </ButtonGroup>
                            </Col>
                            <Col lg={8}>
                                <div className="d-flex align-items-center mb-2">
                                    <h1 className="h2 mb-0 me-3">
                                        {user?.name}
                                    </h1>
                                    {escort?.is_verified && (
                                        <Badge bg="success" className="me-2">
                                            <ShieldCheck
                                                size={14}
                                                className="me-1"
                                            />
                                            Verified
                                        </Badge>
                                    )}
                                    <Badge bg="info">
                                        {user?.gender || "Female"}
                                    </Badge>
                                </div>

                                <div className="d-flex align-items-center mb-3">
                                    <div className="me-3">
                                        {renderStars(escort?.rating)}
                                        <span className="ms-2">
                                            {escort?.rating || "N/A"} (
                                            {escort?.review_count || 0} reviews)
                                        </span>
                                    </div>
                                    <div className="me-3">
                                        <Eye size={16} className="me-1" />
                                        {escort?.view_count || 0} views
                                    </div>
                                </div>

                                <div className="row mb-3">
                                    <div className="col-md-6">
                                        <ListGroup
                                            variant="flush"
                                            className="bg-transparent"
                                        >
                                            <ListGroup.Item className="bg-transparent text-white border-0 px-0">
                                                <MapPin
                                                    size={16}
                                                    className="me-2"
                                                />
                                                {user?.location ||
                                                    "Nairobi, Kenya"}
                                            </ListGroup.Item>
                                            <ListGroup.Item className="bg-transparent text-white border-0 px-0">
                                                <Clock
                                                    size={16}
                                                    className="me-2"
                                                />
                                                Available Now
                                            </ListGroup.Item>
                                            <ListGroup.Item className="bg-transparent text-white border-0 px-0">
                                                <Award
                                                    size={16}
                                                    className="me-2"
                                                />
                                                {escort?.total_bookings || 0}+
                                                Successful Dates
                                            </ListGroup.Item>
                                        </ListGroup>
                                    </div>
                                    <div className="col-md-6">
                                        <ListGroup
                                            variant="flush"
                                            className="bg-transparent"
                                        >
                                            <ListGroup.Item className="bg-transparent text-white border-0 px-0">
                                                <CalendarIcon
                                                    size={16}
                                                    className="me-2"
                                                />
                                                24/7 Availability
                                            </ListGroup.Item>
                                            <ListGroup.Item className="bg-transparent text-white border-0 px-0">
                                                <UserCheck
                                                    size={16}
                                                    className="me-2"
                                                />
                                                Accepts New Clients
                                            </ListGroup.Item>
                                            <ListGroup.Item className="bg-transparent text-white border-0 px-0">
                                                <Globe
                                                    size={16}
                                                    className="me-2"
                                                />
                                                Speaks {languages.length}{" "}
                                                languages
                                            </ListGroup.Item>
                                        </ListGroup>
                                    </div>
                                </div>

                                <div className="d-flex flex-wrap gap-2 mb-4">
                                    {escort?.featured && (
                                        <Badge
                                            bg="danger"
                                            className="px-3 py-2"
                                        >
                                            <Star size={14} className="me-1" />
                                            Featured
                                        </Badge>
                                    )}
                                    {escort?.incall_available && (
                                        <Badge bg="info" className="px-3 py-2">
                                            In-call Available
                                        </Badge>
                                    )}
                                    {escort?.outcall_available && (
                                        <Badge
                                            bg="success"
                                            className="px-3 py-2"
                                        >
                                            Out-call Available
                                        </Badge>
                                    )}
                                </div>
                            </Col>
                        </Row>
                    </Container>
                </Card.Body>
            </Card>

            {/* Main Content */}
            <Container className="py-4">
                <Row className="g-3">
                    <Col lg={9}>
                        {/* Gallery Preview */}
                        <Card className="mb-4 shadow-sm">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center mb-3">
                                    <h5 className="mb-0">
                                        <ImageIcon size={20} className="me-2" />
                                        {`${user?.name}'s`} Gallery
                                    </h5>
                                </div>

                                {/* Images Display */}
                                <Row>
                                    {galleryImages?.length > 0 ? (
                                        galleryImages
                                            .slice(0, 4)
                                            .map((img, index) => (
                                                <Col
                                                    key={img.id ?? index}
                                                    xs={6}
                                                    md={3}
                                                    className="mb-3"
                                                >
                                                    <div
                                                        className="position-relative rounded overflow-hidden"
                                                        style={{
                                                            height: 200,
                                                            cursor: "pointer",
                                                        }}
                                                        onClick={() => {
                                                            setShowGalleryModal(
                                                                true,
                                                            );
                                                        }}
                                                    >
                                                        <Image
                                                            src={img.url}
                                                            alt="Gallery"
                                                            className="w-100 h-100 object-fit-cover"
                                                        />

                                                        {img.type ===
                                                            "video" && (
                                                            <div className="position-absolute top-50 start-50 translate-middle">
                                                                <Play
                                                                    size={32}
                                                                    className="text-white"
                                                                />
                                                            </div>
                                                        )}

                                                        {img.verified && (
                                                            <Badge
                                                                bg="success"
                                                                className="position-absolute top-0 end-0 m-2"
                                                            >
                                                                <CheckCircle
                                                                    size={12}
                                                                />
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </Col>
                                            ))
                                    ) : (
                                        <Col xs={12}>
                                            <p className="text-center text-white-50 mb-0 py-5">
                                                <FileImage size={80} />
                                                <h6>No images found</h6>
                                            </p>
                                        </Col>
                                    )}
                                </Row>
                            </Card.Body>
                        </Card>

                        <Card className="shadow-sm">
                            <Card.Body>
                                <Tabs
                                    activeKey={activeTab}
                                    onSelect={(key) => setActiveTab(key)}
                                >
                                    <Tab
                                        className="py-4"
                                        eventKey="overview"
                                        title="Overview"
                                    >
                                        <div>
                                            <h4>About Me</h4>
                                            <p className="lead">
                                                {escort?.bio ||
                                                    "No bio available"}
                                            </p>

                                            <Row className="mt-4">
                                                <Col md={6}>
                                                    <h5>Physical Attributes</h5>
                                                    <ListGroup variant="flush">
                                                        {attributes?.map(
                                                            (attr) => (
                                                                <ListGroup.Item
                                                                    key={attr}
                                                                >
                                                                    <CheckCircle
                                                                        size={
                                                                            20
                                                                        }
                                                                        className="text-success me-2"
                                                                    />
                                                                    <strong>
                                                                        {
                                                                            attr.name
                                                                        }
                                                                        :
                                                                    </strong>{" "}
                                                                    {attr.value ||
                                                                        "N/A"}
                                                                </ListGroup.Item>
                                                            ),
                                                        )}
                                                    </ListGroup>
                                                </Col>
                                                <Col md={6}>
                                                    <h5>Languages</h5>
                                                    <div className="d-flex flex-wrap gap-2 mb-3">
                                                        {languages.map(
                                                            (lang, index) => (
                                                                <Badge
                                                                    key={index}
                                                                    bg="light"
                                                                    text="dark"
                                                                    className="px-3 py-2"
                                                                >
                                                                    {lang}
                                                                </Badge>
                                                            ),
                                                        )}
                                                    </div>

                                                    <h5>Special Features</h5>
                                                    <div className="d-flex flex-wrap gap-2">
                                                        {special_features?.map(
                                                            (
                                                                feature,
                                                                index,
                                                            ) => (
                                                                <Badge
                                                                    key={index}
                                                                    bg="secondary"
                                                                    className="px-3 py-2"
                                                                >
                                                                    {feature}
                                                                </Badge>
                                                            ),
                                                        ) ||
                                                            "No special features listed"}
                                                    </div>
                                                </Col>
                                            </Row>
                                        </div>
                                    </Tab>
                                    <Tab
                                        className="py-4"
                                        eventKey="services"
                                        title="Services & Rates"
                                    >
                                        <div>
                                            <h4>Services & Rates</h4>
                                            <Row className="mt-4">
                                                <Col md={12}>
                                                    <h5>Available Services</h5>
                                                    <ListGroup>
                                                        {services.map(
                                                            (
                                                                service,
                                                                index,
                                                            ) => (
                                                                <ListGroup.Item
                                                                    key={index}
                                                                >
                                                                    <CheckCircle
                                                                        size={
                                                                            20
                                                                        }
                                                                        className="text-success me-2"
                                                                    />
                                                                    {service}
                                                                </ListGroup.Item>
                                                            ),
                                                        )}
                                                    </ListGroup>
                                                </Col>
                                            </Row>

                                            <Alert
                                                variant="info"
                                                className="mt-4"
                                            >
                                                <FaInfoCircle className="me-2" />
                                                All rates are exclusive of
                                                travel expenses. Contact for
                                                custom packages.
                                            </Alert>
                                        </div>
                                    </Tab>
                                    <Tab
                                        className="py-4"
                                        eventKey="reviews"
                                        title="Reviews"
                                    >
                                        <div className="">
                                            <div className="d-flex justify-content-between align-items-center mb-4">
                                                <div>
                                                    <h4 className="mb-0">
                                                        Customer Reviews
                                                    </h4>
                                                    <div className="d-flex align-items-center mt-2">
                                                        <div className="me-3">
                                                            <h2 className="text-gold mb-0">
                                                                {escort?.rating ||
                                                                    "N/A"}
                                                            </h2>
                                                            <div>
                                                                {renderStars(
                                                                    escort?.rating,
                                                                )}
                                                            </div>
                                                            <small className="text-white-50">
                                                                Based on{" "}
                                                                {escort?.review_count ||
                                                                    0}{" "}
                                                                reviews
                                                            </small>
                                                        </div>
                                                        <div className="ms-4">
                                                            {[
                                                                5, 4, 3, 2, 1,
                                                            ].map((star) => (
                                                                <div
                                                                    key={star}
                                                                    className="d-flex align-items-center mb-1"
                                                                >
                                                                    <small className="me-2">
                                                                        {star}{" "}
                                                                        star
                                                                    </small>
                                                                    <ProgressBar
                                                                        now={
                                                                            star *
                                                                            20
                                                                        }
                                                                        className="flex-grow-1"
                                                                        style={{
                                                                            height: "8px",
                                                                        }}
                                                                    />
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                </div>
                                                <Button variant="gold">
                                                    Write a Review
                                                </Button>
                                            </div>

                                            {reviews
                                                .slice(
                                                    0,
                                                    showAllReviews
                                                        ? reviews.length
                                                        : 3,
                                                )
                                                .map((review) => (
                                                    <Card
                                                        key={review.id}
                                                        className="mb-3"
                                                    >
                                                        <Card.Body>
                                                            <div className="d-flex justify-content-between mb-2">
                                                                <div>
                                                                    <strong>
                                                                        {review
                                                                            .user
                                                                            ?.name ||
                                                                            review
                                                                                .user
                                                                                ?.display_name ||
                                                                            "Anonymous"}
                                                                    </strong>{" "}
                                                                    {/* FIX HERE */}
                                                                    {review.verified && (
                                                                        <Badge
                                                                            bg="success"
                                                                            className="ms-2"
                                                                        >
                                                                            <CheckCircle
                                                                                size={
                                                                                    12
                                                                                }
                                                                                className="me-1"
                                                                            />
                                                                            Verified
                                                                        </Badge>
                                                                    )}
                                                                </div>
                                                                <small className="text-white-50">
                                                                    {
                                                                        review.date
                                                                    }
                                                                </small>
                                                            </div>
                                                            <div className="mb-2">
                                                                {renderStars(
                                                                    review.rating,
                                                                )}
                                                            </div>
                                                            <p className="mb-0">
                                                                {review.comment}
                                                            </p>
                                                        </Card.Body>
                                                    </Card>
                                                ))}

                                            {reviews.length > 3 && (
                                                <div className="text-center mt-3">
                                                    <Button
                                                        variant="outline-gold"
                                                        onClick={() =>
                                                            setShowAllReviews(
                                                                !showAllReviews,
                                                            )
                                                        }
                                                    >
                                                        {showAllReviews
                                                            ? "Show Less"
                                                            : `Show All ${reviews.length} Reviews`}
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    </Tab>
                                    <Tab
                                        className="py-4"
                                        eventKey="details"
                                        title="Details"
                                    >
                                        <div>
                                            <Row>
                                                <Col md={6}>
                                                    <h5>Availability</h5>
                                                    <ListGroup variant="flush">
                                                        <ListGroup.Item>
                                                            <strong>
                                                                Status:
                                                            </strong>
                                                            <Badge
                                                                bg={
                                                                    escort?.available
                                                                        ? "success"
                                                                        : "danger"
                                                                }
                                                                className="ms-2"
                                                            >
                                                                {escort?.available
                                                                    ? "Available"
                                                                    : "Unavailable"}
                                                            </Badge>
                                                        </ListGroup.Item>
                                                        <ListGroup.Item>
                                                            <strong>
                                                                Working Hours:
                                                            </strong>{" "}
                                                            {escort?.working_hours ||
                                                                "Flexible"}
                                                        </ListGroup.Item>
                                                        <ListGroup.Item>
                                                            <strong>
                                                                Last Active:
                                                            </strong>{" "}
                                                            2 hours ago
                                                        </ListGroup.Item>
                                                    </ListGroup>

                                                    <h5 className="mt-4">
                                                        Location Details
                                                    </h5>
                                                    <ListGroup variant="flush">
                                                        <ListGroup.Item>
                                                            <MapPin className="me-2" />
                                                            {escort?.county
                                                                ?.name || "N/A"}
                                                            ,{" "}
                                                            {escort?.town
                                                                ?.name || "N/A"}
                                                        </ListGroup.Item>
                                                        <ListGroup.Item>
                                                            <Navigation className="me-2" />
                                                            {escort?.location ||
                                                                "Exact location provided after booking"}
                                                        </ListGroup.Item>
                                                    </ListGroup>
                                                </Col>
                                                <Col md={6}>
                                                    <h5>
                                                        Safety & Verification
                                                    </h5>
                                                    <ListGroup variant="flush">
                                                        <ListGroup.Item>
                                                            <ShieldCheck className="me-2 text-success" />
                                                            ID Verified
                                                        </ListGroup.Item>
                                                        <ListGroup.Item>
                                                            <ShieldCheck className="me-2 text-success" />
                                                            Phone Verified
                                                        </ListGroup.Item>
                                                        <ListGroup.Item>
                                                            <ShieldCheck className="me-2 text-warning" />
                                                            Email Verified
                                                        </ListGroup.Item>
                                                        <ListGroup.Item>
                                                            <Shield className="me-2 text-info" />
                                                            Safety Guidelines
                                                            Followed
                                                        </ListGroup.Item>
                                                    </ListGroup>

                                                    <div className="mt-4">
                                                        <Alert variant="warning">
                                                            <strong>
                                                                Safety First:
                                                            </strong>{" "}
                                                            Always meet in
                                                            public places first.
                                                            Trust your instincts
                                                            and report any
                                                            suspicious behavior.
                                                        </Alert>
                                                    </div>
                                                </Col>
                                            </Row>
                                        </div>
                                    </Tab>
                                </Tabs>
                            </Card.Body>
                        </Card>
                    </Col>

                    {/* Sidebar */}
                    <Col lg={3}>
                        {/* Contact Card */}
                        <Card className="shadow-sm mb-4">
                            <Card.Body>
                                <h5 className="text-center mb-4">
                                    Contact & Book
                                </h5>

                                <Button
                                    variant="gold"
                                    size="lg"
                                    className="w-100 mb-2"
                                    onClick={() => setShowCallModal(true)}
                                >
                                    <MessageCircle className="me-1" size={18} />
                                    Book Now
                                </Button>

                                <div className="text-center">
                                    <small className="text-white-50">
                                        <ShieldCheck
                                            size={14}
                                            className="me-1"
                                        />
                                        Secure Booking • 24/7 Support
                                    </small>
                                </div>
                            </Card.Body>
                        </Card>

                        {/* Stats Card */}
                        <Card className="shadow-sm mt-4">
                            <Card.Body>
                                <h6>Profile Stats</h6>
                                <ListGroup variant="flush">
                                    <ListGroup.Item className="d-flex justify-content-between">
                                        <span>Response Rate</span>
                                        <strong>95%</strong>
                                    </ListGroup.Item>
                                    <ListGroup.Item className="d-flex justify-content-between">
                                        <span>Response Time</span>
                                        <strong>15min</strong>
                                    </ListGroup.Item>
                                    <ListGroup.Item className="d-flex justify-content-between">
                                        <span>Profile Views</span>
                                        <strong>
                                            {escort?.view_count || 0}
                                        </strong>
                                    </ListGroup.Item>
                                    <ListGroup.Item className="d-flex justify-content-between">
                                        <span>Member Since</span>
                                        <strong>2023</strong>
                                    </ListGroup.Item>
                                </ListGroup>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Container>

            {/* Contact Modal */}
            <CallModal
                showCallModal={showCallModal}
                setShowCallModal={setShowCallModal}
                escort={escort}
            />

            {/* Gallery Modal */}
            <GalleryModal
                showGalleryModal={showGalleryModal}
                setShowGalleryModal={setShowGalleryModal}
                galleryImages={galleryImages}
            />
        </AppLayout>
    );
};

export default EscortShow;

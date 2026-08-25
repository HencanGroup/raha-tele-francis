import AppLayout from "@/Layouts/AppLayout";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { useState, useCallback } from "react";
import {
    Container,
    Row,
    Col,
    Button,
    Badge,
    Card,
    Image,
    ListGroup,
    Alert,
    ButtonGroup,
    Tabs,
    Tab,
    Modal,
    Spinner,
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
    EyeOff,
    Users,
    Play,
    Calendar as CalendarIcon,
    UserCheck,
    Shield,
    MessageCircle,
    FileImage,
    Lock,
} from "lucide-react";
import { FaInfoCircle, FaStar } from "react-icons/fa";
import CallModal from "@/Components/Modals/CallModal";
import GalleryModal from "@/Components/Modals/GalleryModal";
import { getProfileImage } from "@/Utils/helpers";
import { toast } from "react-toastify";
import StartChartBtn from "@/Components/ui/StartChartBtn";
import FavoriteButton from "@/Components/Buttons/FavoriteButton";
import ReviewsSection from "@/Components/Reviews/ReviewsSection";
import xios from "@/Utils/xios";

const EscortShow = ({ escort }) => {
    const { user, resources, is_favorited } = escort;

    const { auth } = usePage().props;
    const currentUser = auth?.user;

    const [activeTab, setActiveTab] = useState("overview");
    const [showCallModal, setShowCallModal] = useState(false);
    const [showGalleryModal, setShowGalleryModal] = useState(false);
    const [galleryStartIndex, setGalleryStartIndex] = useState(0);
    const [showAllMediaModal, setShowAllMediaModal] = useState(false);

    // Server-backed unlock state — once the member has paid for this escort,
    // both Call Now buttons dial directly and the paywall modal never opens.
    const [phoneUnlocked, setPhoneUnlocked] = useState(
        escort?.phone_unlocked ?? false
    );

    // Media unlock state — tracks which private media the member has paid for.
    const [unlockedMedia, setUnlockedMedia] = useState(
        escort?.unlocked_media ?? []
    );
    const [unlockModalMedia, setUnlockModalMedia] = useState(null);
    const [unlocking, setUnlocking] = useState(false);

    const mediaUnlockCost = escort?.media_unlock_cost ?? 5;
    const isMember = currentUser?.user_type === "member";

    const realPhone = user?.phone_number;

    /* Unlocked → tel: link (browser dialer); locked → open the paywall modal. */
    const callButtonProps = phoneUnlocked
        ? { href: `tel:${realPhone}` }
        : { onClick: () => setShowCallModal(true) };

    // Show ALL media (photos + videos) — public ones clear, private ones blurred.
    const galleryImages = resources
        .filter((resource) => resource.type === "photo" || resource.type === "video")
        .map((resource) => ({
            ...resource,
            is_unlocked: unlockedMedia.includes(resource.id),
        }));

    /** Whether a media item is private and the member hasn't paid for it. */
    const isLocked = useCallback(
        (media) => !media.is_public && !media.is_unlocked && isMember,
        [isMember]
    );

    /** Open the unlock payment modal for a private media item. */
    const handleUnlockClick = (media) => {
        if (!currentUser) {
            toast.info("Please log in to view private photos.");
            return;
        }
        if (!isMember) {
            toast.info("Only members can unlock private media.");
            return;
        }
        setUnlockModalMedia(media);
    };

    /** Call the unlock API, deduct credits, refresh state. */
    const handleConfirmUnlock = async () => {
        if (!unlockModalMedia || unlocking) return;
        setUnlocking(true);
        try {
            await xios.post(`/api/media/${unlockModalMedia.id}/unlock`);
            setUnlockedMedia((prev) => [...prev, unlockModalMedia.id]);
            setUnlockModalMedia(null);
            toast.success("Photo unlocked!");
        } catch (err) {
            const msg =
                err?.response?.data?.message || "Failed to unlock photo.";
            toast.error(msg);
        } finally {
            setUnlocking(false);
        }
    };

    /** Open gallery modal at a specific image index. */
    const openGalleryAt = (index) => {
        setGalleryStartIndex(index);
        setShowGalleryModal(true);
    };

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
                    <div className="position-absolute top-0 end-0 p-3 z-2 d-flex align-items-center gap-2">
                        <FavoriteButton
                            escortId={escort.id}
                            initialIsFavorite={is_favorited}
                            size="md"
                        />
                        <Button
                            variant="outline-light"
                            size="md"
                            className="rounded-circle p-0 d-flex align-items-center justify-content-center"
                            style={{ width: "38px", height: "38px" }}
                            onClick={handleShare}
                        >
                            <Share size={18} />
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
                                        {...callButtonProps}
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
                                    {galleryImages.length > 8 && (
                                        <Button
                                            variant="outline-light"
                                            size="sm"
                                            onClick={() => setShowAllMediaModal(true)}
                                        >
                                            View All ({galleryImages.length})
                                        </Button>
                                    )}
                                </div>

                                {/* Grid — 4 columns, up to 8 items. */}
                                {galleryImages?.length > 0 ? (
                                    <Row className="g-2">
                                        {galleryImages.slice(0, 8).map((img, index) => (
                                            <Col key={img.id ?? index} xs={6} sm={4} md={3}>
                                                <div
                                                    className="position-relative rounded overflow-hidden"
                                                    style={{ height: 180, cursor: "pointer" }}
                                                    onClick={() => {
                                                        if (isLocked(img)) {
                                                            handleUnlockClick(img);
                                                        } else {
                                                            openGalleryAt(index);
                                                        }
                                                    }}
                                                >
                                                    {img.type === "video" ? (
                                                        <video
                                                            src={
                                                                isLocked(img)
                                                                    ? (img.thumbnail_url || img.url)
                                                                    : img.url
                                                            }
                                                            muted
                                                            loop
                                                            className="w-100 h-100 object-fit-cover"
                                                            style={
                                                                isLocked(img)
                                                                    ? { filter: "blur(12px)", transform: "scale(1.1)" }
                                                                    : {}
                                                            }
                                                        />
                                                    ) : (
                                                        <Image
                                                            src={
                                                                isLocked(img)
                                                                    ? (img.thumbnail_url || img.url)
                                                                    : img.url
                                                            }
                                                            alt="Gallery"
                                                            className="w-100 h-100 object-fit-cover"
                                                            style={
                                                                isLocked(img)
                                                                    ? { filter: "blur(12px)", transform: "scale(1.1)" }
                                                                    : {}
                                                            }
                                                        />
                                                    )}

                                                    {img.type === "video" && (
                                                        <div className="position-absolute top-50 start-50 translate-middle">
                                                            <Play size={28} className="text-white" />
                                                        </div>
                                                    )}

                                                    {isLocked(img) && (
                                                        <div
                                                            className="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center"
                                                            style={{ background: "rgba(0,0,0,0.45)" }}
                                                        >
                                                            <Lock size={24} className="text-white mb-1" />
                                                            <small className="text-white fw-semibold">Private</small>
                                                            <small className="text-white-50" style={{ fontSize: "0.7rem" }}>
                                                                Tap to unlock ({mediaUnlockCost} credits)
                                                            </small>
                                                        </div>
                                                    )}

                                                    {img.verified && (
                                                        <Badge bg="success" className="position-absolute top-0 end-0 m-2">
                                                            <CheckCircle size={12} />
                                                        </Badge>
                                                    )}
                                                </div>
                                            </Col>
                                        ))}
                                    </Row>
                                ) : (
                                    <div className="text-center text-white-50 mb-0 py-5">
                                        <FileImage size={80} />
                                        <h6>No media found</h6>
                                    </div>
                                )}
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
                                                                    key={attr.name}
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
                                        <ReviewsSection
                                            escortId={escort.id}
                                            rating={escort?.rating}
                                            reviewCount={escort?.review_count}
                                        />
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
                                    Contact & Call
                                </h5>

                                <Button
                                    variant="gold"
                                    size="lg"
                                    className="w-100 mb-2"
                                    {...callButtonProps}
                                >
                                    <Phone className="me-1" size={18} />
                                    Call Now
                                </Button>

                                <div className="text-center">
                                    <small className="text-white-50">
                                        <ShieldCheck
                                            size={14}
                                            className="me-1"
                                        />
                                        Secure Calls • 24/7 Support
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
                initiallyUnlocked={phoneUnlocked}
                onUnlocked={() => setPhoneUnlocked(true)}
            />

            {/* View All Media Modal — grid of all media items. */}
            <Modal
                show={showAllMediaModal}
                onHide={() => setShowAllMediaModal(false)}
                size="xl"
                centered
            >
                <Modal.Header closeButton>
                    <Modal.Title>
                        <ImageIcon size={18} className="me-2" />
                        {user?.name}'s Gallery ({galleryImages.length})
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Row className="g-2">
                        {galleryImages.map((img, index) => (
                            <Col key={img.id ?? index} xs={6} sm={4} md={3}>
                                <div
                                    className="position-relative rounded overflow-hidden"
                                    style={{ height: 180, cursor: "pointer" }}
                                    onClick={() => {
                                        setShowAllMediaModal(false);
                                        if (isLocked(img)) {
                                            handleUnlockClick(img);
                                        } else {
                                            openGalleryAt(index);
                                        }
                                    }}
                                >
                                    {img.type === "video" ? (
                                        <video
                                            src={
                                                isLocked(img)
                                                    ? (img.thumbnail_url || img.url)
                                                    : img.url
                                            }
                                            muted
                                            loop
                                            className="w-100 h-100 object-fit-cover"
                                            style={
                                                isLocked(img)
                                                    ? { filter: "blur(12px)", transform: "scale(1.1)" }
                                                    : {}
                                            }
                                        />
                                    ) : (
                                        <Image
                                            src={
                                                isLocked(img)
                                                    ? (img.thumbnail_url || img.url)
                                                    : img.url
                                            }
                                            alt="Gallery"
                                            className="w-100 h-100 object-fit-cover"
                                            style={
                                                isLocked(img)
                                                    ? { filter: "blur(12px)", transform: "scale(1.1)" }
                                                    : {}
                                            }
                                        />
                                    )}

                                    {img.type === "video" && (
                                        <div className="position-absolute top-50 start-50 translate-middle">
                                            <Play size={28} className="text-white" />
                                        </div>
                                    )}

                                    {isLocked(img) && (
                                        <div
                                            className="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center"
                                            style={{ background: "rgba(0,0,0,0.45)" }}
                                        >
                                            <Lock size={24} className="text-white mb-1" />
                                            <small className="text-white fw-semibold">Private</small>
                                        </div>
                                    )}

                                    {img.verified && (
                                        <Badge bg="success" className="position-absolute top-0 end-0 m-1">
                                            <CheckCircle size={10} />
                                        </Badge>
                                    )}
                                </div>
                            </Col>
                        ))}
                    </Row>
                </Modal.Body>
            </Modal>

            {/* Gallery Modal — all media; locked items show the unlock prompt. */}
            <GalleryModal
                showGalleryModal={showGalleryModal}
                setShowGalleryModal={setShowGalleryModal}
                galleryImages={galleryImages}
                startIndex={galleryStartIndex}
                isLocked={isLocked}
                onUnlockClick={handleUnlockClick}
                mediaUnlockCost={mediaUnlockCost}
            />

            {/* Media Unlock Modal — pay credits to view a private photo. */}
            <Modal
                show={!!unlockModalMedia}
                onHide={() => setUnlockModalMedia(null)}
                centered
                size="sm"
            >
                <Modal.Header closeButton>
                    <Modal.Title className="fw-semibold">
                        <Lock size={18} className="me-2" />
                        Unlock Private Photo
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body className="text-center">
                    {unlockModalMedia && (
                        <>
                            <div
                                className="rounded overflow-hidden mb-3 mx-auto"
                                style={{ maxWidth: 260, height: 200 }}
                            >
                                <Image
                                    src={
                                        unlockModalMedia.thumbnail_url ||
                                        unlockModalMedia.url
                                    }
                                    alt="Private"
                                    className="w-100 h-100 object-fit-cover"
                                    style={{
                                        filter: "blur(8px)",
                                        transform: "scale(1.1)",
                                    }}
                                />
                            </div>
                            <p className="mb-1">
                                This photo is private. Pay{" "}
                                <strong>{mediaUnlockCost} credits</strong> to
                                view it.
                            </p>
                            <p className="text-muted mb-0" style={{ fontSize: "0.8rem" }}>
                                You have <strong>{currentUser?.credits ?? 0}</strong> credits.
                            </p>
                        </>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button
                        variant="secondary"
                        onClick={() => setUnlockModalMedia(null)}
                        disabled={unlocking}
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="warning"
                        onClick={handleConfirmUnlock}
                        disabled={
                            unlocking ||
                            (currentUser?.credits ?? 0) < mediaUnlockCost
                        }
                    >
                        {unlocking ? (
                            <>
                                <Spinner size="sm" className="me-1" />
                                Unlocking...
                            </>
                        ) : (
                            <>
                                <Eye size={16} className="me-1" />
                                Unlock ({mediaUnlockCost} credits)
                            </>
                        )}
                    </Button>
                </Modal.Footer>
            </Modal>
        </AppLayout>
    );
};

export default EscortShow;

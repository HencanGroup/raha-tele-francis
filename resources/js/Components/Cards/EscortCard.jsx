import { getProfileImage } from "@/Utils/helpers";
import { Link } from "@inertiajs/react";
import { Badge, Button, ButtonGroup, Card, Row, Col } from "react-bootstrap";
import { motion } from "framer-motion";
import StartChartBtn from "../ui/StartChartBtn";
import axios from "axios";

const EscortCard = ({ escort, serviceOptions, viewMode = "grid" }) => {
    // Access the nested profile data
    const profile = escort?.escort_profile;

    const getLocationDisplay = () => {
        return escort.location || `${escort.town_id}, ${escort.county_id}`;
    };

    // Parse JSON strings with safe fallbacks
    const services = profile.services
        ? typeof profile.services === "string"
            ? JSON.parse(profile.services)
            : profile.services
        : [];

    // Animation variants
    const cardVariants = {
        hidden: { opacity: 0, y: 20 },
        visible: {
            opacity: 1,
            y: 0,
            transition: {
                duration: 0.4,
                ease: "easeOut",
            },
        },
        hover: {
            y: -5,
            scale: 1.02,
            transition: {
                duration: 0.2,
                ease: "easeInOut",
            },
        },
        tap: {
            scale: 0.98,
        },
    };

    const imageVariants = {
        initial: { scale: 1 },
        hover: {
            scale: 1.05,
            transition: {
                duration: 0.3,
                ease: "easeOut",
            },
        },
    };

    const badgeVariants = {
        initial: { scale: 0, opacity: 0 },
        animate: {
            scale: 1,
            opacity: 1,
            transition: {
                type: "spring",
                stiffness: 300,
                damping: 15,
            },
        },
        hover: {
            scale: 1.1,
            transition: { duration: 0.2 },
        },
    };

    const serviceBadgeVariants = {
        initial: { opacity: 0, scale: 0.8 },
        animate: (i) => ({
            opacity: 1,
            scale: 1,
            transition: {
                delay: i * 0.05,
                duration: 0.3,
                ease: "backOut",
            },
        }),
        hover: {
            scale: 1.1,
            transition: { duration: 0.1 },
        },
    };

    const actionButtons = (
        <>
            <Button
                variant="outline-light rounded"
                size="sm"
                as={Link}
                href={route("escort.show", profile?.id)}
                className="position-relative overflow-hidden"
            >
                <motion.span
                    className="absolute inset-0 bg-white"
                    initial={{ x: "-100%" }}
                    whileHover={{ x: "100%" }}
                    transition={{ duration: 0.3 }}
                    style={{
                        position: "absolute",
                        top: 0,
                        left: 0,
                        width: "100%",
                        height: "100%",
                        background: "rgba(255,255,255,0.2)",
                        zIndex: 0,
                    }}
                />
                <i className="bi bi-info-circle me-1"></i>
                <span className="position-relative z-1">Details</span>
            </Button>
            <StartChartBtn
                user={escort}
                className={"btn-gold rounded"}
                displayText={
                    <>
                        <i className="bi bi-calendar-plus me-1"></i>
                        <span className="position-relative z-1">Book</span>
                    </>
                }
            />
        </>
    );

    // Favorite button animation
    const FavoriteButton = ({ isList = false }) => {
        const handleFavorite = async (e) => {
            e.preventDefault();
            e.stopPropagation();
            try {
                await axios.post(route("favorites.toggle"), {
                    escort_id: escort.id,
                });
            } catch (error) {
                console.error("Error toggling favorite:", error);
            }
        };

        return (
            <motion.div
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{
                    type: "spring",
                    stiffness: 260,
                    damping: 20,
                    delay: 0.1,
                }}
                whileHover={{
                    scale: 1.2,
                    rotate: [0, -10, 10, -10, 0],
                    transition: { duration: 0.5 },
                }}
                whileTap={{ scale: 0.9 }}
            >
                <Button
                    variant="light"
                    size="sm"
                    className={`${
                        isList ? "rounded-circle" : "rounded-circle shadow"
                    }`}
                    style={{
                        width: "36px",
                        height: "36px",
                        padding: "0",
                    }}
                    title="Add to favorites"
                    onClick={handleFavorite}
                >
                    <motion.i
                        className="bi bi-heart"
                        animate={{ scale: [1, 1.1, 1] }}
                        transition={{ repeat: Infinity, duration: 2, delay: 1 }}
                    />
                </Button>
            </motion.div>
        );
    };

    // Grid View Layout
    const GridView = () => (
        <motion.div
            variants={cardVariants}
            initial="hidden"
            animate="visible"
            whileHover="hover"
            whileTap="tap"
        >
            <Card className="h-100 shadow-sm overflow-hidden">
                {/* Card Image with hover effect */}
                <motion.div
                    className="position-relative overflow-hidden"
                    variants={imageVariants}
                    initial="initial"
                    whileHover="hover"
                >
                    <Card.Img
                        variant="top"
                        src={getProfileImage(escort)}
                        alt={escort.name}
                        className="object-fit-cover"
                        style={{ height: "250px" }}
                    />

                    {/* Gradient overlay on hover */}
                    <motion.div
                        className="position-absolute top-0 start-0 w-100 h-100"
                        initial={{ opacity: 0 }}
                        whileHover={{ opacity: 1 }}
                        transition={{ duration: 0.3 }}
                        style={{
                            background:
                                "linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%)",
                        }}
                    />

                    {/* Available Status */}
                    {profile.available && (
                        <motion.div
                            variants={badgeVariants}
                            initial="initial"
                            animate="animate"
                            whileHover="hover"
                        >
                            <Badge
                                bg="success"
                                className="position-absolute top-0 end-0 m-2"
                            >
                                <i className="bi bi-circle-fill me-1"></i>
                                <motion.span
                                    animate={{ opacity: [0.7, 1, 0.7] }}
                                    transition={{
                                        repeat: Infinity,
                                        duration: 2,
                                    }}
                                >
                                    Available
                                </motion.span>
                            </Badge>
                        </motion.div>
                    )}

                    {/* Verified Badge */}
                    {profile.is_verified && (
                        <motion.div
                            variants={badgeVariants}
                            initial="initial"
                            animate="animate"
                            whileHover="hover"
                        >
                            <Badge
                                bg="warning"
                                text="dark"
                                className="position-absolute top-0 start-0 m-2"
                            >
                                <motion.i
                                    className="bi bi-shield-check me-1"
                                    animate={{ rotate: [0, 10, 0] }}
                                    transition={{
                                        repeat: Infinity,
                                        duration: 3,
                                    }}
                                />
                                Verified
                            </Badge>
                        </motion.div>
                    )}

                    {/* Featured Badge */}
                    {profile.featured && (
                        <motion.div
                            variants={badgeVariants}
                            initial="initial"
                            animate="animate"
                            whileHover="hover"
                        >
                            <Badge
                                bg="danger"
                                className="position-absolute bottom-0 start-0 m-2"
                            >
                                <motion.i
                                    className="bi bi-star-fill me-1"
                                    animate={{ rotateY: [0, 360] }}
                                    transition={{
                                        repeat: Infinity,
                                        duration: 4,
                                        ease: "linear",
                                    }}
                                />
                                Featured
                            </Badge>
                        </motion.div>
                    )}

                    {/* Favorite Button */}
                    <div className="position-absolute bottom-0 end-0 m-2">
                        <FavoriteButton />
                    </div>
                </motion.div>

                <Card.Body className="d-flex flex-column">
                    {/* Header with fade-in animation */}
                    <motion.div
                        className="d-flex justify-content-between align-items-start mb-2"
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.1, duration: 0.3 }}
                    >
                        <div>
                            <Card.Title className="text-white mb-1">
                                {escort.name}, {profile.age}
                            </Card.Title>
                            <Card.Subtitle className="text-secondary mb-2">
                                <i className="bi bi-geo-alt me-1"></i>
                                {getLocationDisplay()}
                            </Card.Subtitle>
                        </div>
                        <div className="text-end">
                            <motion.div
                                whileHover={{ scale: 1.1 }}
                                transition={{ type: "spring", stiffness: 300 }}
                            >
                                <Badge
                                    bg="dark"
                                    className="px-2 py-1 mb-1 d-block"
                                >
                                    <i className="bi bi-star-fill text-warning me-1"></i>
                                    {profile.rating || "0.0"}
                                </Badge>
                            </motion.div>
                        </div>
                    </motion.div>

                    {/* Services with staggered animation */}
                    {services.length > 0 && (
                        <motion.div
                            className="mb-3 flex-grow-1"
                            initial="initial"
                            animate="animate"
                        >
                            <small className="text-secondary d-block mb-1">
                                <i className="bi bi-heart me-1"></i>
                                Services:
                            </small>
                            <div className="d-flex flex-wrap gap-1">
                                {services.slice(0, 2).map((service, idx) => {
                                    const matchingService =
                                        serviceOptions?.find((s) =>
                                            service
                                                .toLowerCase()
                                                .includes(
                                                    s.keyword?.toLowerCase(),
                                                ),
                                        ) || { emoji: "💝" };
                                    return (
                                        <motion.div
                                            key={idx}
                                            custom={idx}
                                            variants={serviceBadgeVariants}
                                            initial="initial"
                                            animate="animate"
                                            whileHover="hover"
                                        >
                                            <Badge
                                                bg="secondary"
                                                className="px-2 py-1"
                                            >
                                                {matchingService.emoji}{" "}
                                                {service}
                                            </Badge>
                                        </motion.div>
                                    );
                                })}
                                {services.length > 2 && (
                                    <motion.div
                                        custom={2}
                                        variants={serviceBadgeVariants}
                                        initial="initial"
                                        animate="animate"
                                        whileHover="hover"
                                    >
                                        <Badge
                                            bg="secondary"
                                            className="px-2 py-1"
                                        >
                                            +{services.length - 2} more
                                        </Badge>
                                    </motion.div>
                                )}
                            </div>
                        </motion.div>
                    )}

                    {/* Short Description */}
                    {profile.description &&
                        profile.description.length > 100 && (
                            <motion.div
                                className="mb-3"
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                transition={{ delay: 0.2 }}
                            >
                                <p className="text-light small mb-0">
                                    {profile.description.substring(0, 100)}...
                                </p>
                            </motion.div>
                        )}

                    {/* Action Buttons */}
                    <motion.div
                        className="d-flex gap-2 mt-auto"
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.3, duration: 0.4 }}
                    >
                        <ButtonGroup className="d-flex gap-2 w-100">
                            {actionButtons}
                        </ButtonGroup>
                    </motion.div>
                </Card.Body>
            </Card>
        </motion.div>
    );

    // List View Layout
    const ListView = () => (
        <motion.div
            variants={cardVariants}
            initial="hidden"
            animate="visible"
            whileHover="hover"
            whileTap="tap"
        >
            <Card className="shadow-sm">
                <Card.Body>
                    <Row className="g-3">
                        {/* Image Column */}
                        <Col xs="auto">
                            <motion.div
                                className="position-relative overflow-hidden rounded"
                                variants={imageVariants}
                                initial="initial"
                                whileHover="hover"
                            >
                                <img
                                    src={getProfileImage(escort)}
                                    alt={escort.name}
                                    className="w-100 h-100 object-fit-cover"
                                />

                                {/* Gradient overlay */}
                                <motion.div
                                    className="position-absolute top-0 start-0 w-100 h-100"
                                    initial={{ opacity: 0 }}
                                    whileHover={{ opacity: 1 }}
                                    transition={{ duration: 0.3 }}
                                    style={{
                                        background:
                                            "linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 60%)",
                                    }}
                                />

                                {/* Badges on image */}
                                {profile.available && (
                                    <motion.div
                                        variants={badgeVariants}
                                        initial="initial"
                                        animate="animate"
                                        whileHover="hover"
                                    >
                                        <Badge
                                            bg="success"
                                            className="position-absolute top-0 end-0 m-1"
                                            style={{ fontSize: "0.7rem" }}
                                        >
                                            <i className="bi bi-circle-fill me-1"></i>
                                        </Badge>
                                    </motion.div>
                                )}
                                {profile.is_verified && (
                                    <motion.div
                                        variants={badgeVariants}
                                        initial="initial"
                                        animate="animate"
                                        whileHover="hover"
                                    >
                                        <Badge
                                            bg="warning"
                                            text="dark"
                                            className="position-absolute top-0 start-0 m-1"
                                            style={{ fontSize: "0.7rem" }}
                                        >
                                            <i className="bi bi-shield-check"></i>
                                        </Badge>
                                    </motion.div>
                                )}
                                {profile.featured && (
                                    <motion.div
                                        variants={badgeVariants}
                                        initial="initial"
                                        animate="animate"
                                        whileHover="hover"
                                    >
                                        <Badge
                                            bg="danger"
                                            className="position-absolute bottom-0 start-0 m-1"
                                            style={{ fontSize: "0.7rem" }}
                                        >
                                            <i className="bi bi-star-fill"></i>
                                        </Badge>
                                    </motion.div>
                                )}
                            </motion.div>
                        </Col>

                        {/* Content Column */}
                        <Col>
                            <div className="d-flex justify-content-between align-items-start">
                                {/* Main Info */}
                                <div className="flex-grow-1">
                                    <motion.div
                                        className="d-flex align-items-center gap-2 mb-2"
                                        initial={{ opacity: 0, x: -20 }}
                                        animate={{ opacity: 1, x: 0 }}
                                        transition={{ duration: 0.3 }}
                                    >
                                        <h5 className="text-white mb-0">
                                            {escort.name}, {profile.age}
                                        </h5>
                                        <motion.div
                                            whileHover={{ scale: 1.1 }}
                                            transition={{
                                                type: "spring",
                                                stiffness: 300,
                                            }}
                                        >
                                            <Badge
                                                bg="dark"
                                                className="px-2 py-1"
                                            >
                                                <i className="bi bi-star-fill text-warning me-1"></i>
                                                {profile.rating || "0.0"}
                                            </Badge>
                                        </motion.div>
                                    </motion.div>

                                    {/* Location */}
                                    <motion.div
                                        className="d-flex align-items-center gap-3 mb-2"
                                        initial={{ opacity: 0 }}
                                        animate={{ opacity: 1 }}
                                        transition={{
                                            delay: 0.1,
                                            duration: 0.3,
                                        }}
                                    >
                                        <span className="text-secondary">
                                            <i className="bi bi-geo-alt me-1"></i>
                                            {getLocationDisplay()}
                                        </span>
                                    </motion.div>

                                    {/* Description */}
                                    {profile.description && (
                                        <motion.p
                                            className="text-light small mb-3"
                                            initial={{ opacity: 0 }}
                                            animate={{ opacity: 1 }}
                                            transition={{
                                                delay: 0.2,
                                                duration: 0.4,
                                            }}
                                        >
                                            {profile.description.length > 200
                                                ? `${profile.description.substring(
                                                      0,
                                                      200,
                                                  )}...`
                                                : profile.description}
                                        </motion.p>
                                    )}

                                    {/* Services - Expanded in List View */}
                                    {services.length > 0 && (
                                        <motion.div
                                            className="mb-3"
                                            initial="initial"
                                            animate="animate"
                                        >
                                            <small className="text-secondary d-block mb-1">
                                                <i className="bi bi-heart me-1"></i>
                                                Services:
                                            </small>
                                            <div className="d-flex flex-wrap gap-1">
                                                {services.map(
                                                    (service, idx) => {
                                                        const matchingService =
                                                            serviceOptions?.find(
                                                                (s) =>
                                                                    service
                                                                        .toLowerCase()
                                                                        .includes(
                                                                            s.keyword?.toLowerCase(),
                                                                        ),
                                                            ) || {
                                                                emoji: "💝",
                                                            };
                                                        return (
                                                            <motion.div
                                                                key={idx}
                                                                custom={idx}
                                                                variants={
                                                                    serviceBadgeVariants
                                                                }
                                                                initial="initial"
                                                                animate="animate"
                                                                whileHover="hover"
                                                            >
                                                                <Badge
                                                                    bg="secondary"
                                                                    className="px-2 py-1"
                                                                >
                                                                    {
                                                                        matchingService.emoji
                                                                    }{" "}
                                                                    {service}
                                                                </Badge>
                                                            </motion.div>
                                                        );
                                                    },
                                                )}
                                            </div>
                                        </motion.div>
                                    )}
                                </div>

                                {/* Action Buttons - Vertical in List View */}
                                <motion.div
                                    className="d-flex flex-column text-nowrap gap-2 ms-3"
                                    initial={{ opacity: 0, x: 20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    transition={{ delay: 0.3, duration: 0.4 }}
                                >
                                    {actionButtons}
                                    <FavoriteButton isList={true} />
                                </motion.div>
                            </div>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>
        </motion.div>
    );

    // Return the appropriate view based on viewMode prop
    return viewMode === "list" ? <ListView /> : <GridView />;
};

export default EscortCard;

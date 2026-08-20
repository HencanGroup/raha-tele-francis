import { getProfileImage } from "@/Utils/helpers";
import { Link } from "@inertiajs/react";
import { Badge, Button, ButtonGroup, Card, Row, Col } from "react-bootstrap";
import { motion } from "framer-motion";
import StartChartBtn from "../ui/StartChartBtn";

/**
 * Card for the member listing shown to escort accounts on /escort.
 * Members carry no escort profile, so the card uses plain user fields.
 */
const MemberCard = ({ member, viewMode = "grid" }) => {
    const cardVariants = {
        hidden: { opacity: 0, y: 20 },
        visible: {
            opacity: 1,
            y: 0,
            transition: { duration: 0.4, ease: "easeOut" },
        },
        hover: { y: -5, scale: 1.02 },
        tap: { scale: 0.98 },
    };

    const actionButtons = (
        <ButtonGroup className="d-flex gap-2 w-100">
            <Button
                variant="outline-light rounded"
                size="sm"
                as={Link}
                href={route("member.show", member.member_id)}
                className="position-relative overflow-hidden"
            >
                <i className="bi bi-info-circle me-1"></i>
                <span className="position-relative z-1">Profile</span>
            </Button>
            <StartChartBtn
                user={member}
                className={"btn-gold rounded"}
                displayText={
                    <>
                        <i className="bi bi-chat-dots me-1"></i>
                        <span className="position-relative z-1">Chat</span>
                    </>
                }
            />
        </ButtonGroup>
    );

    const identity = [member.age, member.gender].filter(Boolean).join(", ");

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
                <Card.Img
                    variant="top"
                    src={getProfileImage(member)}
                    alt={member.display_name || member.name}
                    className="object-fit-cover"
                    style={{ height: "250px" }}
                />

                <Card.Body className="d-flex flex-column">
                    <div className="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <Card.Title className="text-white mb-1">
                                {member.display_name || member.name}
                                {identity && (
                                    <small className="text-secondary ms-2">
                                        {identity}
                                    </small>
                                )}
                            </Card.Title>
                            <Card.Subtitle className="text-secondary mb-2">
                                <i className="bi bi-geo-alt me-1"></i>
                                {member.location || "Kenya"}
                            </Card.Subtitle>
                        </div>
                        <Badge bg="dark" className="px-2 py-1 mb-1 d-block">
                            <i className="bi bi-person-badge text-warning me-1"></i>
                            Member
                        </Badge>
                    </div>

                    <motion.div className="d-flex gap-2 mt-auto pt-3">
                        {actionButtons}
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
                    <Row className="g-3 align-items-center">
                        <Col xs="auto">
                            <img
                                src={getProfileImage(member)}
                                alt={member.display_name || member.name}
                                className="rounded object-fit-cover"
                                style={{ width: 110, height: 110 }}
                            />
                        </Col>
                        <Col>
                            <h5 className="text-white mb-1">
                                {member.display_name || member.name}
                                {identity && (
                                    <small className="text-secondary ms-2">
                                        {identity}
                                    </small>
                                )}
                            </h5>
                            <span className="text-secondary">
                                <i className="bi bi-geo-alt me-1"></i>
                                {member.location || "Kenya"}
                            </span>
                        </Col>
                        <Col md="auto" className="d-flex flex-column gap-2">
                            {actionButtons}
                        </Col>
                    </Row>
                </Card.Body>
            </Card>
        </motion.div>
    );

    return viewMode === "list" ? <ListView /> : <GridView />;
};

export default MemberCard;
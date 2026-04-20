import DashboardStatsCard from "@/Components/Cards/DashboardStatsCard";
import AppLayout from "@/Layouts/AppLayout";
import { Head, usePage } from "@inertiajs/react";
import { Col, Container, Row, Card, Badge, Button } from "react-bootstrap";
import { Link } from "@inertiajs/react";
import { useState, useMemo } from "react";
import { toast } from "react-toastify";

const Member = ({ dashboardData }) => {
    const { auth } = usePage().props;

    // State for filters and search
    const [searchTerm, setSearchTerm] = useState("");
    const [statusFilter, setStatusFilter] = useState("all");
    const [priorityFilter, setPriorityFilter] = useState("all");
    const [sortBy, setSortBy] = useState("last_message_time");

    // Destructure data from backend
    const { stats = [], conversations = [] } = dashboardData || {};

    // Filter and sort conversations
    const filteredConversations = useMemo(() => {
        return conversations
            .filter((conv) => {
                // Search filter
                const matchesSearch =
                    searchTerm === "" ||
                    conv.contact
                        ?.toLowerCase()
                        .includes(searchTerm.toLowerCase()) ||
                    conv.last_message
                        ?.toLowerCase()
                        .includes(searchTerm.toLowerCase());

                // Status filter
                const matchesStatus =
                    statusFilter === "all" || conv.status === statusFilter;

                // Priority filter
                const matchesPriority =
                    priorityFilter === "all" ||
                    conv.priority === priorityFilter;

                return matchesSearch && matchesStatus && matchesPriority;
            })
            .sort((a, b) => {
                switch (sortBy) {
                    case "last_message_time":
                        return (
                            new Date(b.last_message_time) -
                            new Date(a.last_message_time)
                        );
                    case "priority":
                        const priorityOrder = {
                            high: 3,
                            medium: 2,
                            low: 1,
                            normal: 0,
                        };
                        return (
                            (priorityOrder[b.priority] || 0) -
                            (priorityOrder[a.priority] || 0)
                        );
                    case "unread_count":
                        return (b.unread_count || 0) - (a.unread_count || 0);
                    case "contact":
                        return (a.contact || "").localeCompare(b.contact || "");
                    default:
                        return 0;
                }
            });
    }, [conversations, searchTerm, statusFilter, priorityFilter, sortBy]);

    // Handle conversation archive
    const handleArchiveConversation = (conversationId, event) => {
        event.stopPropagation();
        if (
            window.confirm(
                "Are you sure you want to archive this conversation?"
            )
        ) {
            toast.info("Archiving conversation...");
            // Add your API call here
        }
    };

    // Get status badge color
    const getStatusColor = (status) => {
        const colors = {
            active: "success",
            pending: "warning",
            archived: "secondary",
            blocked: "danger",
            resolved: "info",
        };
        return colors[status] || "secondary";
    };

    // Get priority badge color
    const getPriorityColor = (priority) => {
        const colors = {
            high: "danger",
            medium: "warning",
            low: "info",
            normal: "secondary",
        };
        return colors[priority] || "secondary";
    };

    // Format relative time
    const formatRelativeTime = (dateString) => {
        if (!dateString) return "No activity";

        const now = new Date();
        const date = new Date(dateString);
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return "Just now";
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return date.toLocaleDateString();
    };

    // Truncate text
    const truncateText = (text, length = 60) => {
        if (!text) return "";
        return text.length > length ? text.substring(0, length) + "..." : text;
    };

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <Container className="py-4">
                <Row className="g-3">
                    {/* Hearder */}
                    <Col lg={12}>
                        <Card>
                            <Card.Body>
                                Welcome back, {auth?.user?.name}!
                            </Card.Body>
                        </Card>
                    </Col>

                    {stats?.map((stat, index) => (
                        <Col key={index} lg={3} md={6}>
                            <DashboardStatsCard {...stat} />
                        </Col>
                    ))}

                    <Col lg={12}>
                        <Card className="shadow-sm border-0">
                            <Card.Body className="p-0">
                                {filteredConversations.length === 0 ? (
                                    <div className="text-center py-5 my-5">
                                        <i className="bi bi-chat-left display-4 text-white-50 mb-3"></i>
                                        <h5 className="text-white-50">
                                            No conversations found
                                        </h5>
                                        <p className="text-white-50 mb-4">
                                            {searchTerm ||
                                            statusFilter !== "all" ||
                                            priorityFilter !== "all"
                                                ? "Try adjusting your filters or search term"
                                                : "Start a new conversation to get started"}
                                        </p>
                                        <Link
                                            href={route("chat.index")}
                                            className="btn btn-gold"
                                        >
                                            <i className="bi bi-plus-circle me-1"></i>
                                            Start New Conversation
                                        </Link>
                                    </div>
                                ) : (
                                    <></>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Container>
        </AppLayout>
    );
};

export default Member;

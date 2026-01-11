import { Link } from "@inertiajs/react";
import React from "react";
import { Card, Badge } from "react-bootstrap";

const DashboardStatsCard = ({
    title,
    value,
    description,
    link = "#",
    icon,
    color = "primary",
    trend = null,
    trendDirection = "neutral",
}) => {
    const trendColors = {
        up: "success",
        down: "danger",
        neutral: "secondary",
    };

    const trendIcons = {
        up: "📈",
        down: "📉",
        neutral: "➖",
    };

    return (
        <Card
            as={Link}
            href={link}
            className="stats-card h-100 text-decoration-none shadow-sm border-0"
            style={{ transition: "all 0.2s ease-in-out" }}
        >
            <Card.Body className="d-flex justify-content-between align-items-center">
                {/* Left Content */}
                <div>
                    <h6 className={`text-${color} fw-semibold mb-1`}>
                        {title}
                    </h6>

                    <h2 className="fw-bold mb-1">{value}</h2>

                    {description && (
                        <small className="text-white-50 d-block">
                            {description}
                        </small>
                    )}

                    {trend && (
                        <Badge
                            bg={trendColors[trendDirection]}
                            className="mt-2"
                        >
                            {trendIcons[trendDirection]} {trend}
                        </Badge>
                    )}
                </div>

                {/* Icon */}
                {icon && (
                    <div
                        className={`rounded-circle bg-${color} bg-opacity-10 d-flex align-items-center justify-content-center`}
                        style={{
                            width: 56,
                            height: 56,
                            fontSize: 28,
                        }}
                    >
                        {icon}
                    </div>
                )}
            </Card.Body>
        </Card>
    );
};

export default DashboardStatsCard;

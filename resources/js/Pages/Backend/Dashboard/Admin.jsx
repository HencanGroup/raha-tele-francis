import DashboardStatsCard from "@/Components/Cards/DashboardStatsCard";
import AppLayout from "@/Layouts/AppLayout";
import { Head, usePage } from "@inertiajs/react";
import { Col, Container, Row } from "react-bootstrap";

const Admin = ({ dashboardData }) => {
    const { auth } = usePage().props;

    // Destructure data from backend
    const { stats = [] } = dashboardData || {};

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <Container className="py-4">
                <Row className="g-3">
                    {/* Hearder */}
                    <Col lg={12}>
                        <h1>Dashboard</h1>
                        <p className="text-white-50">
                            Welcome back, {auth?.user?.name}!
                        </p>
                    </Col>

                    {stats?.map((stat, index) => (
                        <Col key={index} lg={3} md={6}>
                            <DashboardStatsCard {...stat} />
                        </Col>
                    ))}
                </Row>
            </Container>
        </AppLayout>
    );
};

export default Admin;

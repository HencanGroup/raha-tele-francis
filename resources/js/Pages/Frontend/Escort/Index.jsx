import EscortsList from "@/Components/Partials/EscortsList";
import AppLayout from "@/Layouts/AppLayout";
import { Container, Button, ButtonGroup } from "react-bootstrap";
import { useState } from "react";
import { usePage } from "@inertiajs/react";

const Escorts = () => {
    const { auth } = usePage().props;
    const isEscort = auth?.user?.user_type === "escort";

    const [showFilters, setShowFilters] = useState(false);

    const handleToggleFilters = () => {
        setShowFilters(!showFilters);
    };

    return (
        <AppLayout>
            <Container className="pt-4">
                {/* Header Section with Controls */}
                <div className="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 className="mb-2">
                            {isEscort ? "Members" : "Premium Escorts"}
                        </h1>
                        <p className="text-white-50">
                            {isEscort
                                ? "Browse members on the platform"
                                : "Browse our exclusive selection of premium escorts"}
                        </p>
                    </div>

                    <ButtonGroup className="ga-2">
                        <Button
                            variant={
                                showFilters ? "warning" : "outline-warning"
                            }
                            onClick={handleToggleFilters}
                            size="sm"
                            className="d-flex align-items-center"
                        >
                            <i
                                className={`bi ${
                                    showFilters ? "bi-funnel-fill" : "bi-funnel"
                                }`}
                            ></i>
                        </Button>
                    </ButtonGroup>
                </div>
            </Container>

            {/* Main Content */}
            <EscortsList
                showFilters={showFilters}
                escortsPerPage={24}
                listingType={isEscort ? "members" : "escorts"}
            />
        </AppLayout>
    );
};

export default Escorts;
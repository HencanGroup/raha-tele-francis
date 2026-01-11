import EscortsList from "@/Components/Pages/EscortsList";
import AppLayout from "@/Layouts/AppLayout";
import { Container, Button, Form, ButtonGroup } from "react-bootstrap";
import { useState } from "react";

const Escorts = () => {
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
                        <h1 className="mb-2">Premium Escorts</h1>
                        <p className="text-white-50">
                            Browse our exclusive selection of premium escorts
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
            <EscortsList showFilters={showFilters} escortsPerPage={24} />
        </AppLayout>
    );
};

export default Escorts;

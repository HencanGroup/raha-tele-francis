import useData from "@/Hooks/useData";
import { useDivHeights } from "@/Hooks/useSizes";
import { useState, useEffect, useMemo, useCallback } from "react";
import {
    Container,
    Row,
    Col,
    Button,
    Form,
    Badge,
    Spinner,
    Dropdown,
} from "react-bootstrap";
import EscortCard from "../Cards/EscortCard";
import { usePage } from "@inertiajs/react";

const EscortsList = ({ showFilters = true, escortsPerPage = 12 }) => {
    const { escortServices } = usePage().props;
    const navbarHeight = useDivHeights("escort-navbar");

    const {
        counties,
        towns,
        filterTownsByCounty,
        isLoading: dataLoading,
    } = useData();

    /* ==============================
       STATE
    ============================== */

    const [escorts, setEscorts] = useState([]);
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(false);
    const [viewMode, setViewMode] = useState("grid");

    const [filters, setFilters] = useState({
        county: "",
        town: "",
        ageRange: "",
        services: [],
        verifiedOnly: false,
        onlineOnly: false,
        sortBy: "",
    });

    const listingColWidth = showFilters ? 9 : 12;

    /* ==============================
       OPTIONS
    ============================== */

    // for serviceOptions please get random 5 services from escortServices
    const serviceOptions = useMemo(() => {
        const randomServices = [];
        for (let i = 0; i < 5; i++) {
            const randomIndex = Math.floor(
                Math.random() * escortServices.length
            );
            randomServices.push(escortServices[randomIndex]);
        }
        return randomServices;
    }, [escortServices]);

    const ageOptions = [
        { value: "18-25", label: "18-25" },
        { value: "26-30", label: "26-30" },
        { value: "31-35", label: "31-35" },
        { value: "36-40", label: "36-40" },
        { value: "41+", label: "41+" },
    ];

    const sortOptions = [
        { value: "featured", label: "✨ Featured" },
        { value: "rating", label: "⭐ Rating" },
        { value: "newest", label: "🆕 Newest" },
    ];

    /* ==============================
       COMPUTED VALUES
    ============================== */

    const hasActiveFilters = useMemo(() => {
        return Object.entries(filters).some(([key, val]) => {
            if (key === "sortBy") return val !== "featured";
            if (Array.isArray(val)) return val.length > 0;
            if (typeof val === "boolean") return val;
            return val !== "";
        });
    }, [filters]);

    const filteredTowns = useMemo(() => {
        if (!filters.county || !towns.length) return [];
        return towns.filter((town) => {
            const townCountyId = town.county_id || town.countyId;
            const selectedCountyId = parseInt(filters.county);
            return townCountyId?.toString() === selectedCountyId?.toString();
        });
    }, [filters.county, towns]);

    const getSelectedCountyName = useCallback(() => {
        if (!filters.county) return "";
        const county = counties.find(
            (c) => c.id?.toString() === filters.county
        );
        return county ? county.name : filters.county;
    }, [filters.county, counties]);

    const gridColumns = useMemo(() => {
        if (!showFilters) return 4; // Full width grid
        return viewMode === "grid" ? 3 : 1; // 3 columns for grid, 1 for list
    }, [showFilters, viewMode]);

    /* ==============================
       API FETCH
    ============================== */

    const fetchEscorts = useCallback(
        async (page = 1, append = false) => {
            setLoading(true);

            try {
                const params = new URLSearchParams({
                    page,
                    per_page: escortsPerPage,
                    county: filters.county,
                    town: filters.town,
                    age_range: filters.ageRange,
                    verified: filters.verifiedOnly ? 1 : 0,
                    online: filters.onlineOnly ? 1 : 0,
                    services: filters.services.join(","),
                    sort: filters.sortBy,
                });

                const res = await fetch(route("escorts.index") + "?" + params);

                if (!res.ok) {
                    throw new Error(
                        `Escorts request failed with status ${res.status}`
                    );
                }

                const json = await res.json();

                setEscorts((prev) =>
                    append ? [...prev, ...json.data] : json.data
                );

                setCurrentPage(json.current_page);
                setLastPage(json.last_page);
                setTotal(json.total);
            } catch (e) {
                console.error("Failed to fetch escorts", e);
            } finally {
                setLoading(false);
            }
        },
        [filters, escortsPerPage]
    );

    /* ==============================
       EFFECTS
    ============================== */

    useEffect(() => {
        fetchEscorts(1, false);
    }, [fetchEscorts]);

    /* ==============================
       HANDLERS
    ============================== */

    const handleFilterChange = useCallback((name, value) => {
        setFilters((prev) => {
            const updated = { ...prev, [name]: value };
            if (name === "county") updated.town = "";
            return updated;
        });
    }, []);

    const handleServiceToggle = useCallback((id) => {
        setFilters((prev) => ({
            ...prev,
            services: prev.services.includes(id)
                ? prev.services.filter((s) => s !== id)
                : [...prev.services, id],
        }));
    }, []);

    const handlePriceChange = useCallback((type, value) => {
        setFilters((prev) => ({
            ...prev,
            [type]: value ? parseInt(value) : "",
        }));
    }, []);

    const handleViewModeChange = useCallback((mode) => {
        setViewMode(mode);
    }, []);

    const loadMore = () => {
        if (currentPage < lastPage && !loading) {
            fetchEscorts(currentPage + 1, true);
        }
    };

    const resetFilters = () => {
        setFilters({
            county: "",
            town: "",
            ageRange: "",
            services: [],
            verifiedOnly: false,
            onlineOnly: false,
            sortBy: "",
        });
    };

    /* ==============================
       COMPONENTS
    ============================== */

    const FilterSidebar = () => (
        <Col lg={3} className="d-none d-lg-block">
            <div
                className="filter-sidebar sticky-top rounded bg-dark bg-opacity-25"
                style={{ top: `${navbarHeight}px` }}
            >
                {/* Filter Header */}
                <div className="filter-header d-flex justify-content-between align-items-center p-3 border-bottom border-secondary">
                    <h5 className="mb-0 d-flex align-items-center gap-2">
                        <i className="bi bi-funnel-fill text-warning"></i>
                        Filters
                    </h5>
                    <Button
                        variant="link"
                        className="text-warning p-0"
                        onClick={resetFilters}
                        title="Reset all filters"
                    >
                        <i className="bi bi-arrow-clockwise"></i>
                    </Button>
                </div>

                {/* Filter Content */}
                <div className="filter-content p-3">
                    {/* Sort By */}
                    <div className="mb-4">
                        <h6 className="mb-2 d-flex align-items-center gap-2">
                            <i className="bi bi-sort-down text-warning"></i>
                            Sort By
                        </h6>
                        <Form.Select
                            value={filters.sortBy}
                            onChange={(e) =>
                                handleFilterChange("sortBy", e.target.value)
                            }
                            className="bg-dark border-secondary text-white"
                            disabled={dataLoading}
                        >
                            <option value="">🔽 Default</option>
                            {sortOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </Form.Select>
                    </div>

                    {/* County Filter */}
                    <div className="mb-4">
                        <h6 className="mb-2 d-flex align-items-center gap-2">
                            <i className="bi bi-geo-alt text-warning"></i>
                            County
                        </h6>
                        <Form.Select
                            value={filters.county}
                            onChange={(e) => {
                                handleFilterChange("county", e.target.value);
                                if (e.target.value) {
                                    filterTownsByCounty(e.target.value);
                                }
                            }}
                            className="bg-dark border-secondary text-white"
                            disabled={dataLoading}
                        >
                            <option value="">📍 All Counties</option>
                            {counties.map((county) => (
                                <option key={county.id} value={county.id}>
                                    {county.name}
                                </option>
                            ))}
                        </Form.Select>
                        {dataLoading && (
                            <small className="text-warning d-block mt-1">
                                <Spinner
                                    animation="border"
                                    size="sm"
                                    className="me-2"
                                />
                                Loading counties...
                            </small>
                        )}
                    </div>

                    {/* Town Filter */}
                    <div className="mb-4">
                        <h6 className="mb-2 d-flex align-items-center gap-2">
                            <i className="bi bi-geo text-warning"></i>
                            Town
                        </h6>
                        <Form.Select
                            value={filters.town}
                            onChange={(e) =>
                                handleFilterChange("town", e.target.value)
                            }
                            className="bg-dark border-secondary text-white"
                            disabled={!filters.county || dataLoading}
                        >
                            <option value="">🏘️ All Towns</option>
                            {filteredTowns.map((town) => (
                                <option
                                    key={town.id}
                                    value={town.id || town.name}
                                >
                                    {town.name}
                                </option>
                            ))}
                        </Form.Select>
                        {!filters.county && (
                            <small className="text-muted d-block mt-1">
                                Select a county first
                            </small>
                        )}
                        {filters.county && filteredTowns.length === 0 && (
                            <small className="text-warning d-block mt-1">
                                No towns found for this county
                            </small>
                        )}
                    </div>

                    {/* Age Range */}
                    <div className="mb-4">
                        <h6 className="mb-2 d-flex align-items-center gap-2">
                            <i className="bi bi-person text-warning"></i>
                            Age Range
                        </h6>
                        <div className="d-flex flex-wrap gap-2">
                            {ageOptions.map((option) => (
                                <Button
                                    key={option.value}
                                    variant={
                                        filters.ageRange === option.value
                                            ? "warning"
                                            : "outline-secondary"
                                    }
                                    size="sm"
                                    onClick={() =>
                                        handleFilterChange(
                                            "ageRange",
                                            option.value
                                        )
                                    }
                                    className="rounded-pill px-3"
                                >
                                    {option.label}
                                </Button>
                            ))}
                        </div>
                    </div>

                    {/* Services */}
                    <div className="mb-4">
                        <h6 className="mb-2 d-flex align-items-center gap-2">
                            <i className="bi bi-heart text-warning"></i>
                            Services
                        </h6>
                        <div className="d-flex flex-column gap-2">
                            {serviceOptions.map((service) => (
                                <Form.Check
                                    key={service}
                                    type="checkbox"
                                    id={`service-${service}`}
                                    label={service}
                                    checked={filters.services.includes(service)}
                                    onChange={() =>
                                        handleServiceToggle(service)
                                    }
                                    className="text-white"
                                />
                            ))}
                        </div>
                    </div>

                    {/* Quick Filters */}
                    <div className="mb-4">
                        <h6 className="mb-2 d-flex align-items-center gap-2">
                            <i className="bi bi-lightning text-warning"></i>
                            Quick Filters
                        </h6>
                        <div className="d-flex flex-column gap-2">
                            <Form.Check
                                type="checkbox"
                                id="verified-only"
                                label={
                                    <span className="text-white">
                                        <i className="bi bi-shield-check text-success me-2"></i>
                                        Verified Only
                                    </span>
                                }
                                checked={filters.verifiedOnly}
                                onChange={(e) =>
                                    handleFilterChange(
                                        "verifiedOnly",
                                        e.target.checked
                                    )
                                }
                            />
                            <Form.Check
                                type="checkbox"
                                id="online-only"
                                label={
                                    <span className="text-white">
                                        <i className="bi bi-circle-fill text-success me-2"></i>
                                        Online Now
                                    </span>
                                }
                                checked={filters.onlineOnly}
                                onChange={(e) =>
                                    handleFilterChange(
                                        "onlineOnly",
                                        e.target.checked
                                    )
                                }
                            />
                        </div>
                    </div>

                    {/* Active Filters */}
                    {hasActiveFilters && (
                        <div className="mt-4 p-3 rounded bg-dark">
                            <h6 className="mb-2 text-white">Active Filters:</h6>
                            <div className="d-flex flex-wrap gap-2">
                                {filters.county && (
                                    <Badge bg="secondary" className="px-2 py-1">
                                        📍 {getSelectedCountyName()}
                                    </Badge>
                                )}
                                {filters.town && (
                                    <Badge bg="secondary" className="px-2 py-1">
                                        🏘️{" "}
                                        {filteredTowns.find(
                                            (t) =>
                                                t.id?.toString() ===
                                                    filters.town.toString() ||
                                                t.name === filters.town
                                        )?.name || filters.town}
                                    </Badge>
                                )}
                                {filters.ageRange && (
                                    <Badge bg="secondary" className="px-2 py-1">
                                        👤 {filters.ageRange}
                                    </Badge>
                                )}
                                {filters.services.map((service) => (
                                    <Badge
                                        key={service}
                                        bg="secondary"
                                        className="px-2 py-1"
                                    >
                                        {
                                            serviceOptions
                                                .find((s) => s.id === service)
                                                ?.label?.split(" ")[0]
                                        }
                                    </Badge>
                                ))}
                                {filters.verifiedOnly && (
                                    <Badge bg="success" className="px-2 py-1">
                                        ✅ Verified
                                    </Badge>
                                )}
                                {filters.onlineOnly && (
                                    <Badge bg="info" className="px-2 py-1">
                                        🟢 Online
                                    </Badge>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </Col>
    );

    const MobileFilterToggle = () => (
        <div className="d-lg-none mb-4">
            <Button
                variant="outline-warning"
                className="w-100 d-flex justify-content-between align-items-center"
                onClick={() => {
                    /* Add mobile filter modal/drawer logic */
                }}
            >
                <span>
                    <i className="bi bi-funnel-fill me-2"></i>
                    Filters{" "}
                    {hasActiveFilters &&
                        `(${
                            Object.keys(filters).filter(
                                (k) => filters[k] && k !== "sortBy"
                            ).length
                        })`}
                </span>
                <i className="bi bi-chevron-down"></i>
            </Button>
        </div>
    );

    return (
        <Container data-bs-theme="dark">
            <Row className="g-4">
                {/* Conditionally render filter sidebar */}
                {showFilters && <FilterSidebar />}

                {/* Escorts Listing */}
                <Col lg={listingColWidth}>
                    {/* Mobile Filter Toggle */}
                    {showFilters && <MobileFilterToggle />}

                    {/* Results Info */}
                    <div className="d-flex justify-content-between align-items-center mb-4 p-3 rounded bg-dark bg-opacity-50">
                        <div>
                            <h5 className="mb-0">
                                Showing {escorts.length} of {total} results
                                {filters.county &&
                                    ` in ${getSelectedCountyName()}`}
                            </h5>
                            {loading && (
                                <small className="text-warning">
                                    <Spinner
                                        animation="border"
                                        size="sm"
                                        className="me-2"
                                    />
                                    Loading...
                                </small>
                            )}
                        </div>
                        <Dropdown>
                            <Dropdown.Toggle
                                variant="outline-warning"
                                id="view-options"
                            >
                                <i
                                    className={`bi bi-${
                                        viewMode === "grid" ? "grid" : "list"
                                    } me-2`}
                                ></i>
                                {viewMode === "grid"
                                    ? "Grid View"
                                    : "List View"}
                            </Dropdown.Toggle>
                            <Dropdown.Menu
                                className="bg-dark border-secondary"
                                align={"end"}
                            >
                                <Dropdown.Item
                                    active={viewMode === "grid"}
                                    className="text-white"
                                    onClick={() => handleViewModeChange("grid")}
                                >
                                    <i className="bi bi-grid me-2"></i>
                                    Grid View
                                </Dropdown.Item>
                                <Dropdown.Item
                                    active={viewMode === "list"}
                                    className="text-white"
                                    onClick={() => handleViewModeChange("list")}
                                >
                                    <i className="bi bi-list me-2"></i>
                                    List View
                                </Dropdown.Item>
                            </Dropdown.Menu>
                        </Dropdown>
                    </div>

                    {/* Loading Spinner for Data */}
                    {dataLoading && !loading && (
                        <div className="text-center py-5">
                            <Spinner animation="border" variant="warning" />
                            <p className="mt-3 text-white">
                                Loading location data...
                            </p>
                        </div>
                    )}

                    {/* Loading Spinner for Filters */}
                    {loading && escorts.length === 0 && (
                        <div className="text-center py-5">
                            <Spinner animation="border" variant="warning" />
                            <p className="mt-3 text-white">
                                Loading matches...
                            </p>
                        </div>
                    )}

                    {/* Escorts Grid/List */}
                    {!dataLoading && !loading && escorts.length > 0 && (
                        <>
                            {viewMode === "grid" ? (
                                <Row xs={2} md={gridColumns} className="g-4">
                                    {escorts.map((escort, index) => (
                                        <Col key={escort.id}>
                                            <EscortCard
                                                escort={escort}
                                                serviceOptions={serviceOptions}
                                                index={index}
                                                viewMode={viewMode}
                                            />
                                        </Col>
                                    ))}
                                </Row>
                            ) : (
                                <div className="list-view">
                                    {escorts.map((escort, index) => (
                                        <div key={escort.id} className="mb-3">
                                            <EscortCard
                                                escort={escort}
                                                serviceOptions={serviceOptions}
                                                index={index}
                                                viewMode={viewMode}
                                            />
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Load More */}
                            <div className="text-center py-4">
                                {currentPage < lastPage ? (
                                    <Button
                                        variant="outline-warning"
                                        size="lg"
                                        onClick={loadMore}
                                        className="px-5 py-3"
                                        disabled={loading}
                                    >
                                        {loading ? (
                                            <>
                                                <Spinner
                                                    animation="border"
                                                    size="sm"
                                                    className="me-2"
                                                />
                                                Loading...
                                            </>
                                        ) : (
                                            <>
                                                <i className="bi bi-arrow-down-circle me-2"></i>
                                                Load More (
                                                {total - escorts.length} more)
                                            </>
                                        )}
                                    </Button>
                                ) : (
                                    <div className="alert alert-dark border-warning">
                                        <i className="bi bi-check-circle-fill text-warning me-2"></i>
                                        You've reached the end! All {total}{" "}
                                        escorts displayed.
                                    </div>
                                )}
                            </div>
                        </>
                    )}

                    {/* No Results */}
                    {!dataLoading && !loading && escorts.length === 0 && (
                        <div className="text-center py-5">
                            <div className="empty-state">
                                <i className="bi bi-people display-1 text-secondary"></i>
                                <h4 className="mt-4 text-white">
                                    No matches found
                                </h4>
                                <p className="text-secondary mb-4">
                                    {filters.county
                                        ? `No escorts found in ${getSelectedCountyName()}. Try adjusting your filters or select a different county.`
                                        : "Try adjusting your filters to find more escorts"}
                                </p>
                                <div className="d-flex gap-2 justify-content-center">
                                    <Button
                                        variant="warning"
                                        onClick={resetFilters}
                                        className="px-4"
                                    >
                                        <i className="bi bi-funnel me-2"></i>
                                        Clear All Filters
                                    </Button>
                                    {filters.county && (
                                        <Button
                                            variant="outline-secondary"
                                            onClick={() =>
                                                handleFilterChange("county", "")
                                            }
                                            className="px-4"
                                        >
                                            <i className="bi bi-globe me-2"></i>
                                            View All Counties
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </Col>
            </Row>
        </Container>
    );
};

export default EscortsList;

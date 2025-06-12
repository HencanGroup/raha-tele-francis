import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Col, Container, Row, Form, ButtonGroup, Button, Spinner, Card, Badge } from 'react-bootstrap';
import { motion } from 'framer-motion';
import useLocation from '@/Hooks/useLocation';

export default function EscortsNearMe() {
    const [escorts, setEscorts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [distance, setDistance] = useState(10); // Default distance in km

    // Use the location hook
    const {
        location,
        userLocation,
        fullLocationData,
        locationError,
        isLoadingLocation,
        reverseGeocode
    } = useLocation();

    useEffect(() => {
        const fetchData = async () => {
            try {
                // Get user coordinates from localStorage
                const userLatitude = localStorage.getItem('userLatitude');
                const userLongitude = localStorage.getItem('userLongitude');

                if (!userLatitude || !userLongitude) {
                    throw new Error('Location not available. Please enable location services.');
                }

                setLoading(true);
                const response = await fetch(route('api.nearby.escorts', {
                    lat: userLatitude,
                    lng: userLongitude,
                    distance: distance
                }));

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                // Handle different response formats
                let escortsData = [];
                if (Array.isArray(data)) {
                    escortsData = data;
                } else if (data && data.data && Array.isArray(data.data)) {
                    escortsData = data.data;
                } else if (data && data.results && Array.isArray(data.results)) {
                    escortsData = data.results;
                }

                setEscorts(escortsData);

                // Set location display if available
                if (escortsData.length > 0 && escortsData[0].location_name && !location) {
                    setLocation(escortsData[0].location_name);
                }
            } catch (err) {
                setError(err.message);
                console.error('Error:', err);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, [distance, location]);

    const handleDistanceChange = (newDistance) => {
        setDistance(newDistance);
    };

    const getLocationBadge = () => {
        if (!fullLocationData) return null;

        return (
            <Badge bg="secondary" className="ms-2 location-badge">
                <i className="bi bi-geo-alt"></i> {fullLocationData.city || fullLocationData.county}
                {fullLocationData.country && `, ${fullLocationData.country}`}
            </Badge>
        );
    };

    // Combine loading states
    const isOverallLoading = loading || isLoadingLocation;
    const displayError = error || locationError;

    return (
        <Container className='py-5'>
            <Row>
                {/* Side Nav */}
                <Col md={2}>
                    <div className="">
                        <h5 className="text-white mb-3">
                            <i className="bi bi-filter"></i>
                            Distance
                        </h5>
                        <ButtonGroup vertical className="w-100 mb-3">
                            {[5, 10, 20, 50].map((dist) => (
                                <Button
                                    key={dist}
                                    variant={distance === dist ? 'primary' : 'outline-primary'}
                                    onClick={() => handleDistanceChange(dist)}
                                    className='text-start'
                                >
                                    {dist} km
                                </Button>
                            ))}
                        </ButtonGroup>

                        {fullLocationData && (
                            <div className="location-details p-3 bg-dark rounded">
                                <h6 className="gold-text">
                                    <i className="bi bi-pin-map"></i> Your Location
                                </h6>
                                <small className="text-white-50">
                                    {fullLocationData.fullAddress || `${fullLocationData.city}, ${fullLocationData.country}`}
                                </small>
                            </div>
                        )}
                    </div>
                </Col>

                <Col md={10}>
                    <div className="header mb-4">
                        <div className="d-flex align-items-center">
                            <h3 className="mb-0">
                                Escorts/Singles in{" "}
                                {fullLocationData ? (
                                    <span className="gold-text">
                                        {fullLocationData.city || fullLocationData.county}
                                        {fullLocationData.country && `, ${fullLocationData.country}`}
                                    </span>
                                ) : (
                                    <span className="gold-text">Near You</span>
                                )}
                            </h3>
                            {getLocationBadge()}
                        </div>
                        <small className='text-white-50'>
                            Within {distance} km of your current location
                            {fullLocationData?.city && ` in ${fullLocationData.city}`}
                        </small>
                    </div>

                    <hr className='dashed-hr my-2' />

                    {isOverallLoading ? (
                        <div className="text-center py-5">
                            <Spinner animation="border" variant="primary" />
                            <p className="mt-2 text-white">
                                Finding escorts near you{fullLocationData?.city && ` in ${fullLocationData.city}`}...
                            </p>
                        </div>
                    ) : displayError ? (
                        <div className="alert alert-danger">
                            {displayError}
                            {fullLocationData && (
                                <div className="mt-2">
                                    <small>Your detected location: {fullLocationData.displayName}</small>
                                </div>
                            )}
                        </div>
                    ) : !escorts || escorts.length === 0 ? (
                        <div className="alert alert-info">
                            No escorts found within {distance} km{fullLocationData?.city && ` of ${fullLocationData.city}`}.
                            {fullLocationData && (
                                <Link href={`/escorts?location=${fullLocationData.city}`} className="ms-2">
                                    Browse all escorts in {fullLocationData.city}
                                </Link>
                            )}
                        </div>
                    ) : (
                        <div className="escorts">
                            <Row xs={1} md={2} lg={3} className="g-4">
                                {escorts.map((escort) => (
                                    <Col key={escort.id}>
                                        <motion.div
                                            initial={{ opacity: 0, y: 20 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            transition={{ duration: 0.3 }}
                                        >
                                            <Card className="h-100 escort-card">
                                                <Card.Img
                                                    variant="top"
                                                    src={escort.profile_image || '/images/default-profile.jpg'}
                                                    alt={escort.name}
                                                    className="escort-image"
                                                />
                                                <Card.Body>
                                                    <Card.Title className="gold-text">
                                                        {escort.name}
                                                        {escort.distance && (
                                                            <Badge bg="info" className="ms-2">
                                                                {escort.distance.toFixed(1)} km
                                                            </Badge>
                                                        )}
                                                    </Card.Title>
                                                    <Card.Text>
                                                        <small className="text-muted">
                                                            <i className="bi bi-geo-alt"></i> {escort.location_name}
                                                        </small>
                                                        {escort.age && (
                                                            <small className="text-muted ms-2">
                                                                <i className="bi bi-person"></i> {escort.age}
                                                            </small>
                                                        )}
                                                    </Card.Text>
                                                    <Card.Text className="text-truncate">
                                                        {escort.description}
                                                    </Card.Text>
                                                </Card.Body>
                                                <Card.Footer>
                                                    <Link
                                                        href={`/escorts/${escort.id}`}
                                                        className="btn btn-sm btn-primary w-100"
                                                    >
                                                        View Profile
                                                    </Link>
                                                    {fullLocationData?.city && escort.location_name && (
                                                        <Link
                                                            href={`/escorts?location=${encodeURIComponent(escort.location_name)}`}
                                                            className="btn btn-sm btn-outline-secondary w-100 mt-2"
                                                        >
                                                            More in {escort.location_name}
                                                        </Link>
                                                    )}
                                                </Card.Footer>
                                            </Card>
                                        </motion.div>
                                    </Col>
                                ))}
                            </Row>
                        </div>
                    )}
                </Col>
            </Row>
        </Container>
    );
}
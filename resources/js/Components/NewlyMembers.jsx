import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, ButtonGroup, Button, Spinner } from 'react-bootstrap';
import { motion } from 'framer-motion';

export default function NewlyMembers() {
    const [escorts, setEscorts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Function to calculate age from birth date
    const calculateAge = (birthDate) => {
        if (!birthDate) return null;

        const today = new Date();
        const birthDateObj = new Date(birthDate);
        let age = today.getFullYear() - birthDateObj.getFullYear();
        const monthDiff = today.getMonth() - birthDateObj.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDateObj.getDate())) {
            age--;
        }

        return age;
    };

    // Function to format phone number
    const formatPhoneNumber = (phoneNumber) => {
        if (!phoneNumber) return 'N/A';

        // Remove all non-digit characters
        const cleaned = phoneNumber.replace(/\D/g, '');

        // Check if phone number is long enough to format
        if (cleaned.length <= 6) return phoneNumber;

        const firstFour = cleaned.substring(0, 4);
        const lastTwo = cleaned.substring(cleaned.length - 2);
        const asterisks = '*'.repeat(cleaned.length - 6);

        return `${firstFour}${asterisks}${lastTwo}`;
    };

    useEffect(() => {
        const fetchEscorts = async () => {
            try {
                // Replace this with your actual API endpoint
                const response = await fetch(route("api.new-escorts"));

                if (!response.ok) {
                    throw new Error('Failed to fetch escorts');
                }

                const data = await response.json();

                // Process the data to add calculated age and formatted phone number
                const processedData = data.map(escort => ({
                    ...escort,
                    age: calculateAge(escort.birth_date),
                    formattedPhone: formatPhoneNumber(escort.phone_number)
                }));

                setEscorts(processedData);
                setLoading(false);
            } catch (err) {
                setError(err.message);
                setLoading(false);
            }
        };

        fetchEscorts();
    }, []);

    const fadeInUp = {
        hidden: { opacity: 0, y: 30 },
        visible: { opacity: 1, y: 0 },
    };

    if (loading) {
        return (
            <div className="py-5 text-center">
                <Spinner animation="border" variant="primary" />
                <p className="mt-2">Loading companions...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="py-5 text-center text-danger">
                <i className="bi bi-exclamation-triangle-fill fs-1"></i>
                <p className="mt-2">Error loading companions: {error}</p>
                <Button variant="outline-primary" onClick={() => window.location.reload()}>
                    Try Again
                </Button>
            </div>
        );
    }

    if (escorts.length === 0) {
        return (
            <div className="py-5 text-center">
                <i className="bi bi-people-fill fs-1"></i>
                <p className="mt-2">No companions available at the moment</p>
            </div>
        );
    }

    return (
        <div className="escorts-listing py-5">
            <Container>
                {/* Header Section */}
                <Row className="justify-content-center mb-5">
                    <Col lg={8} className="text-center">
                        <motion.h1
                            className="section-title"
                            initial="hidden"
                            animate="visible"
                            variants={fadeInUp}
                            transition={{ duration: 0.6 }}
                        >
                            Our <span className="gold-text">Exquisite</span> Companions
                        </motion.h1>
                        <motion.div
                            className="divider"
                            initial={{ scaleX: 0 }}
                            animate={{ scaleX: 1 }}
                            transition={{ duration: 0.5, delay: 0.4 }}
                            style={{ originX: 0 }}
                        />
                        <motion.p
                            className="section-subtitle"
                            initial="hidden"
                            animate="visible"
                            variants={fadeInUp}
                            transition={{ duration: 0.6, delay: 0.2 }}
                        >
                            Curated selection of premium companions for discerning clients
                        </motion.p>
                    </Col>
                </Row>

                {/* Escort Cards Grid */}
                <Row className="g-4">
                    {escorts.map((escort, index) => (
                        <Col key={escort.id} xs={6} md={3}>
                            <motion.div
                                className="escort-card-wrapper"
                                initial={{ opacity: 0, y: 50 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.4, delay: index * 0.1 }}
                                viewport={{ once: true }}
                            >
                                <Card className="h-100 escort-card">
                                    {/* Image with overlay */}
                                    <div className="card-image-container">
                                        <Card.Img
                                            variant="top"
                                            src={`/storage/${escort.profile_picture}`}
                                            className="escort-image"
                                            alt={`${escort.name}'s profile`}
                                        />
                                        {escort.premium && <div className="premium-badge">PREMIUM</div>}
                                    </div>

                                    {/* Card Body */}
                                    <Card.Body className="card-body text-capitalize p-3 pb-0">
                                        <div className="name-rating-container mb-2">
                                            <Card.Title className="escort-name text-truncate">
                                                <a href={route("profile.show", escort.id)} className='text-decoration-none text-primary'>
                                                    {escort.name}
                                                </a>
                                            </Card.Title>
                                            <div className="rating-badge">
                                                <i className="bi bi-heart me-1"></i>
                                                <span>{Math.floor((escort.rating || 0) * 20)}%</span>
                                            </div>
                                        </div>

                                        <div className="details-container mb-2">
                                            <div className="location">
                                                <i className="bi bi-geo-alt-fill me-2"></i>
                                                <span>{escort.location || 'Location not specified'}</span>
                                            </div>
                                            <div className="age">{escort.age ? `${escort.age} y.o.` : ''}</div>
                                        </div>

                                        <div className="details-container mb-0">
                                            <small className="text-truncate">
                                                <i className="bi bi-telephone me-2"></i>
                                                <span>{escort.formattedPhone}</span>
                                            </small>
                                            <ButtonGroup className='gap-1'>
                                                <a href={`tel:${escort.phone_number}`}
                                                    className="rating-badge"
                                                >
                                                    <i className="bi bi-telephone"></i>
                                                </a>
                                                <a href={`https://wa.me/${escort.phone_number}`}
                                                    className="rating-badge"
                                                >
                                                    <i className="bi bi-whatsapp"></i>
                                                </a>
                                            </ButtonGroup>
                                        </div>
                                    </Card.Body>
                                </Card>
                            </motion.div>
                        </Col>
                    ))}
                </Row>

                {/* CTA Section */}
                <Row className="justify-content-center mt-6 pt-4">
                    <Col md={8} className="text-center">
                        <motion.div
                            className="d-flex gap-3 justify-content-center"
                            initial="hidden"
                            whileInView="visible"
                            variants={fadeInUp}
                            transition={{ duration: 0.6 }}
                            viewport={{ once: true }}
                        >
                            <Button className="cta-button outline rounded-pill">
                                <i className="bi bi-person"></i>
                                View All
                            </Button>
                        </motion.div>
                    </Col>
                </Row>
            </Container>
        </div>
    );
}
import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Alert, Spinner, Badge } from 'react-bootstrap';
import axios from 'axios';
import { router } from '@inertiajs/react';
import { FiCheck, FiStar, FiAward, FiZap, FiCheckCircle, FiChevronDown, FiChevronUp } from 'react-icons/fi';

export default function SubscriptionPlans() {
    const [plans, setPlans] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedPlan, setSelectedPlan] = useState(null);
    const [hoveredPlan, setHoveredPlan] = useState(null);
    const [expandedPlans, setExpandedPlans] = useState({});

    useEffect(() => {
        const fetchPlans = async () => {
            try {
                const response = await axios.get(route("api.plans"));
                setPlans(response.data);
                setLoading(false);
            } catch (err) {
                setError(err.response?.data?.message || 'Failed to load plans');
                setLoading(false);
            }
        };

        fetchPlans();
    }, []);

    const handleSelectPlan = async (planId) => {
        try {
            setSelectedPlan(planId);

            // Find the selected plan from the plans array
            const selectedPlanObj = plans.find(plan => plan.id === planId);

            if (!selectedPlanObj) {
                throw new Error('Selected plan not found');
            }

            // Create a cart object with only the selected plan
            const cart = {
                items: [{
                    id: selectedPlanObj.id,
                    name: selectedPlanObj.name,
                    price: selectedPlanObj.price,
                    billing_period: selectedPlanObj.billing_period,
                    quantity: 1,
                    features: selectedPlanObj.features
                }],
                total: selectedPlanObj.price,
                count: 1
            };

            // Store the cart in localStorage
            localStorage.setItem('cart', JSON.stringify(cart));

            // Redirect to checkout
            router.visit(route('checkout.index'));
        } catch (err) {
            setError(err.message || 'Failed to select plan');
        }
    };

    const toggleExpandPlan = (planId) => {
        setExpandedPlans(prev => ({
            ...prev,
            [planId]: !prev[planId]
        }));
    };

    const renderFeatures = (plan) => {
        let features = [];

        // Parse features
        if (Array.isArray(plan.features)) {
            features = plan.features;
        } else {
            try {
                const parsed = JSON.parse(plan.features);
                if (Array.isArray(parsed)) {
                    features = parsed;
                }
            } catch (e) {
                if (typeof plan.features === 'string') {
                    features = plan.features.split(',').map(f => f.trim());
                }
            }
        }

        const isExpanded = expandedPlans[plan.id];
        const shouldTruncate = features.length > 5 && !isExpanded;
        const displayedFeatures = shouldTruncate ? features.slice(0, 5) : features;

        return (
            <>
                <ul className="feature-list">
                    {displayedFeatures.map((feature, index) => (
                        <li key={index} className="feature-item">
                            <FiCheckCircle className={`feature-icon ${plan.is_featured ? 'text-primary' : 'text-success'}`} />
                            <span>{feature}</span>
                        </li>
                    ))}
                </ul>
                {features.length > 5 && (
                    <button
                        className="show-more-btn"
                        onClick={() => toggleExpandPlan(plan.id)}
                    >
                        {isExpanded ? 'Show less' : 'Show more'}
                        {isExpanded ? (
                            <FiChevronUp className="show-more-icon" />
                        ) : (
                            <FiChevronDown className="show-more-icon" />
                        )}
                    </button>
                )}
            </>
        );
    };

    if (loading) {
        return (
            <Container className="my-5 py-5 text-center">
                <Spinner animation="border" variant="primary" role="status">
                    <span className="visually-hidden">Loading...</span>
                </Spinner>
                <p className="mt-3 text-white-50">Loading our amazing plans...</p>
            </Container>
        );
    }

    if (error) {
        return (
            <Container className="my-5">
                <Alert variant="danger" className="text-center">
                    <i className="bi bi-exclamation-triangle-fill me-2"></i>
                    {error}
                </Alert>
            </Container>
        );
    }

    return (
        <Container className="py-5">
            <div className="plans-header">
                <h2 className="section-title">Find Your Perfect Plan</h2>
                <div className="divider"></div>
                <p className="section-subtitle">
                    Unlock powerful features and grow your presence with our flexible subscription options
                </p>
            </div>

            {plans.length === 0 && (
                <Alert variant="info" className="text-center">
                    We're currently preparing some amazing plans for you. Please check back soon!
                </Alert>
            )}

            <Row className="g-4 justify-content-center">
                {plans.map((plan) => (
                    <Col key={plan.id} md={4} lg={4}>
                        <div
                            className={`position-relative ${plan.is_featured ? 'mt-0' : 'mt-4'}`}
                            onMouseEnter={() => setHoveredPlan(plan.id)}
                            onMouseLeave={() => setHoveredPlan(null)}
                        >
                            {plan.is_featured && (
                                <Badge pill bg="warning" className="featured-badge p-3">
                                    <FiStar className="me-1" /> Featured Plan
                                </Badge>
                            )}

                            <Card
                                className={`plan-card ${selectedPlan === plan.id ? 'selected' : ''} ${plan.is_featured ? 'featured' : ''}`}
                            >
                                <Card.Header className={`plan-header ${plan.is_featured ? 'featured' : ''}`}>
                                    <h3 className={`plan-name ${plan.is_featured ? 'text-white' : ''}`}>
                                        {plan.name}
                                        {plan.slug === 'vip' && <FiAward className="ms-2" />}
                                    </h3>
                                </Card.Header>
                                <Card.Body className="py-4 px-4">
                                    <div className={`plan-price ${plan.is_featured ? 'text-primary' : ''}`}>
                                        <h2 className="price-amount">
                                            Ksh {plan.price}
                                        </h2>
                                        <small className="price-period">
                                            / {
                                                plan.billing_period >= 365
                                                    ? `${Math.round(plan.billing_period / 365)} year${Math.round(plan.billing_period / 365) !== 1 ? 's' : ''}`
                                                    : plan.billing_period >= 60  // 2+ months → show in months
                                                        ? `${Math.round(plan.billing_period / 30)} month${Math.round(plan.billing_period / 30) !== 1 ? 's' : ''}`
                                                        : plan.billing_period >= 14  // 2+ weeks → show in weeks
                                                            ? `${Math.round(plan.billing_period / 7)} week${Math.round(plan.billing_period / 7) !== 1 ? 's' : ''}`
                                                            : `${plan.billing_period} day${plan.billing_period !== 1 ? 's' : ''}`
                                            }
                                        </small>
                                    </div>

                                    <p className="plan-description">{plan.description}</p>

                                    {renderFeatures(plan)}

                                    <div className="plan-button mt-auto pt-3">
                                        <Button
                                            variant={selectedPlan === plan.id ? 'primary' : plan.is_featured ? 'primary' : 'outline-primary'}
                                            size="lg"
                                            className={`plan-button ${plan.is_featured ? 'shadow' : ''}`}
                                            onClick={() => handleSelectPlan(plan.id)}
                                            disabled={selectedPlan === plan.id}
                                        >
                                            {selectedPlan === plan.id ? (
                                                <>
                                                    <FiCheck className="me-2" /> Selected
                                                </>
                                            ) : (
                                                <>
                                                    {plan.is_featured ? <FiZap className="me-2" /> : null}
                                                    Get {plan.name}
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </Card.Body>
                            </Card>
                        </div>
                    </Col>
                ))}
            </Row>

            <div className="footer-text">
                <p>
                    Need help choosing? <a href="/contact" className="text-decoration-none text-primary">Contact our support team</a> — we're happy to help!
                </p>
                <div className="guarantee-text">
                    <FiCheck className="text-success me-2" />
                    <small>30-day money-back guarantee on all plans</small>
                </div>
            </div>
        </Container>
    );
}
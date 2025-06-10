import React from 'react';
import { Container, Row, Col } from 'react-bootstrap';

export default function SuccessStories() {
    const testimonials = [
        {
            id: 1,
            clientName: "James Wilson",
            clientTitle: "Entrepreneur, London",
            content: "Meeting Sophia through this service transformed my business trips. Her companionship was elegant, discreet, and exactly what I needed to feel comfortable in new cities.",
            rating: 5,
            image: "/storage/images/avatar.png"
        },
        {
            id: 2,
            clientName: "Sarah Chen",
            clientTitle: "CEO, Tech Startup",
            content: "As a female executive, safety and professionalism were my top concerns. Isabella exceeded all expectations - she's now my go-to companion for all major events.",
            rating: 5,
            image: "/storage/images/avatar.png"
        },
        {
            id: 3,
            clientName: "Michael Rodriguez",
            clientTitle: "Investor, Dubai",
            content: "The quality of companions here is unmatched. Olivia helped me navigate multiple high-profile social events with perfect poise. Worth every penny.",
            rating: 4,
            image: "/storage/images/avatar.png"
        }
    ];

    return (
        <Container fluid className="success-stories-section py-5">
            <Row className="justify-content-center mb-5">
                <Col lg={8} className="text-center">
                    <h2 className="section-title">
                        <span className="gold-text">Verified</span> Success Stories
                    </h2>
                    <div className="divider"></div>
                    <p className="section-subtitle">
                        Hear from our satisfied clients about their exceptional experiences
                    </p>
                </Col>
            </Row>

            <Row className="g-4 justify-content-center">
                {testimonials.map((testimonial) => (
                    <Col key={testimonial.id} lg={4} md={6}>
                        <div className="testimonial-card">
                            <div className="client-rating">
                                {[...Array(5)].map((_, i) => (
                                    <i
                                        key={i}
                                        className={`fas fa-star ${i < testimonial.rating ? 'filled' : 'empty'}`}
                                    ></i>
                                ))}
                            </div>
                            <p className="testimonial-content">"{testimonial.content}"</p>

                            <div className="client-info">
                                <img
                                    src={testimonial.image}
                                    alt={testimonial.clientName}
                                    className="client-image"
                                />
                                <div>
                                    <h4 className="client-name">{testimonial.clientName}</h4>
                                    <p className="client-title">{testimonial.clientTitle}</p>
                                </div>
                            </div>
                        </div>
                    </Col>
                ))}
            </Row>
        </Container>
    );
}
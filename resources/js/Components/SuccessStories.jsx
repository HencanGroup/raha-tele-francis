import React from 'react';
import { Container, Row, Col } from 'react-bootstrap';

export default function SuccessStories() {
    const testimonials = [
        {
            id: 1,
            clientName: "Mutuku John",
            clientTitle: "Kitui, Kenya",
            content: "Kumfahamu Sophia kupitia huduma hii ilibadilisha safari zangu za biashara. Urafiki wake ulikuwa wa kifahari, wa kujificha, na hasa nilichohitaji kujisikia salama katika miji mpya.",
            rating: 5,
            image: "/storage/images/avatar.png"
        },
        {
            id: 2,
            clientName: "Onyango",
            clientTitle: "Kisumu",
            content: "Kama meneja wa kike, usalama na uzoefu vilikuwa muhimu kwangu. Isabella alizidi matarajio yote - sasa ni mwenzi wangu wa kudumu kwa hafla zote kubwa.",
            rating: 5,
            image: "/storage/images/avatar.png"
        },
        {
            id: 3,
            clientName: "Kariuki Peter",
            clientTitle: "Nairobi, Kenya",
            content: "Ubora wa waenzi hapa hauna kifani. Olivia alinisaidia katika hafla nyingi za kijamii za hadhi ya juu kwa ujasiri kamili. Thamani yake ni ya kila senti.",
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
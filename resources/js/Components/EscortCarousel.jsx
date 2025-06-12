import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Col, Container, Row, Form, ButtonGroup } from 'react-bootstrap';
import { motion, AnimatePresence } from 'framer-motion';
import useCounty from '@/Hooks/useCounty';

const backgroundImages = [
    'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
    'https://images.unsplash.com/photo-1517841905240-472988babdf9?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
    'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
    'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'
];

export default function EscortCarousel() {
    const [searchData, setSearchData] = useState({
        location: '',
        service: '',
        ageRange: '',
        availability: 'now'
    });

    const { counties } = useCounty();

    const [currentImageIndex, setCurrentImageIndex] = useState(0);

    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentImageIndex((prevIndex) =>
                prevIndex === backgroundImages.length - 1 ? 0 : prevIndex + 1
            );
        }, 5000);

        return () => clearInterval(interval);
    }, []);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setSearchData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
    };

    return (
        <div className="escort-carousel d-flex justify-content-center align-items-center">
            {/* Background images with fade transition */}
            <AnimatePresence mode='wait'>
                <motion.div
                    key={currentImageIndex}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 1.5 }}
                    className="background-image"
                    style={{
                        backgroundImage: `url(${backgroundImages[currentImageIndex]})`
                    }}
                />
            </AnimatePresence>

            {/* Gradient overlay */}
            <div className="gradient-overlay" />

            <Container className='py-5'>
                <Row>
                    {/* notes */}
                    <Col md={7} className='carousel-left-content mb-4'>
                        <div className="w-100">
                            <h1>
                                Meet <span className="gold-text">Singles</span> near you
                            </h1>
                            <h5>
                                Start finding your match for free today!
                            </h5>
                            <p>
                                Connect with like-minded individuals for dating, relationships, or more. Discover the possibilities around you!
                            </p>
                            <ButtonGroup className='gap-3'>
                                <Link
                                    as={"button"}
                                    href={route("register")}
                                    className='submit-button rounded-3 text-nowrap'
                                >
                                    Become Member
                                </Link>
                                {/* <Link
                                    as={"button"}
                                    className='btn btn-outline-light border-2 rounded-3 text-nowrap'
                                >
                                    Success Stories
                                </Link> */}
                            </ButtonGroup>
                        </div>
                    </Col>

                    {/* Search overlay */}
                    <Col md={5} className='search-overlay p-0'>
                        <div className="search-form-container p-5">
                            <h2 className="search-title text-start">
                                Find Your <span>Perfect</span> Partner
                            </h2>
                            <p>
                                Serious dating with your perfect match is just a click away.
                            </p>
                            <Form onSubmit={handleSubmit}>
                                <div className="form-group">
                                    {/* <label htmlFor="location" className="form-label">Location</label> */}
                                    <select
                                        name="location"
                                        id="location"
                                        className="form-control"
                                        value={searchData.location}
                                        onChange={handleChange}
                                        required
                                    >
                                        <option value="">Select Location</option>
                                        {counties.map((county) => (
                                            <option key={county.toLowerCase()} value={county.toLowerCase()}>
                                                {county}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="form-group">
                                    {/* <label htmlFor="service" className="form-label">Service Type</label> */}
                                    <select
                                        name="service"
                                        id="service"
                                        className="form-control"
                                        value={searchData.service}
                                        onChange={handleChange}
                                    >
                                        <option value="">Any Service</option>
                                        <option value="dinner">Dinner Date</option>
                                        <option value="event">Event Companion</option>
                                        <option value="travel">Travel Companion</option>
                                        <option value="overnight">Overnight Stay</option>
                                    </select>
                                </div>

                                <div className="form-group">
                                    {/* <label htmlFor="ageRange" className="form-label">Age Range</label> */}
                                    <select
                                        name="ageRange"
                                        id="ageRange"
                                        className="form-control"
                                        value={searchData.ageRange}
                                        onChange={handleChange}
                                    >
                                        <option value="">Any Age</option>
                                        <option value="18-25">18-25</option>
                                        <option value="26-30">26-30</option>
                                        <option value="31-35">31-35</option>
                                        <option value="36+">36+</option>
                                    </select>
                                </div>

                                <div className="form-group">
                                    {/* <label htmlFor="availability" className="form-label">Availability</label> */}
                                    <select
                                        name="availability"
                                        id="availability"
                                        className="form-control"
                                        value={searchData.availability}
                                        onChange={handleChange}
                                    >
                                        <option value="now">Available Now</option>
                                        <option value="today">Today</option>
                                        <option value="weekend">This Weekend</option>
                                        <option value="specific">Specific Date</option>
                                    </select>
                                </div>

                                <button type="submit" className="submit-button">
                                    Search Companions
                                </button>
                            </Form>
                        </div>
                    </Col>
                </Row>
            </Container>
        </div>
    );
}
import { useState, useEffect } from "react";
import { Link } from "@inertiajs/react";
import { Col, Container, Row, Form } from "react-bootstrap";
import { motion, AnimatePresence } from "framer-motion";
import useData from "@/Hooks/useData";

const backgroundImages = [
    "https://images.unsplash.com/photo-1524504388940-b1c1722653e1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1900&q=80",
    "https://images.unsplash.com/photo-1517841905240-472988babdf9?ixlib=rb-1.2.1&auto=format&fit=crop&w=1900&q=80",
    "https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=1900&q=80",
    "https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1900&q=80",
];

export default function HeroSection() {
    const { counties } = useData();
    const [searchData, setSearchData] = useState({
        location: "",
        service: "",
        ageRange: "",
        availability: "now",
    });

    const [currentImageIndex, setCurrentImageIndex] = useState(0);
    const [isLoaded, setIsLoaded] = useState(false);

    // Preload images
    useEffect(() => {
        const preloadImages = () => {
            backgroundImages.forEach((src) => {
                const img = new Image();
                img.src = src;
            });
        };
        preloadImages();
        setIsLoaded(true);
    }, []);

    // Background image rotation
    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentImageIndex((prevIndex) =>
                prevIndex === backgroundImages.length - 1 ? 0 : prevIndex + 1
            );
        }, 6000);

        return () => clearInterval(interval);
    }, []);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setSearchData((prev) => ({
            ...prev,
            [name]: value,
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log("Search submitted:", searchData);
        // Add your search logic here
    };

    // Services options with icons
    const serviceOptions = [
        { value: "dinner", label: "🍽️ Dinner Date" },
        { value: "event", label: "🎭 Event Companion" },
        { value: "travel", label: "✈️ Travel Companion" },
        { value: "overnight", label: "🌙 Overnight Stay" },
        { value: "longterm", label: "💝 Long-term Relationship" },
        { value: "companionship", label: "👥 General Companionship" },
    ];

    // Age range options
    const ageOptions = [
        { value: "18-25", label: "18-25 years" },
        { value: "26-30", label: "26-30 years" },
        { value: "31-35", label: "31-35 years" },
        { value: "36-40", label: "36-40 years" },
        { value: "41-45", label: "41-45 years" },
        { value: "46+", label: "46+ years" },
    ];

    // Availability options
    const availabilityOptions = [
        { value: "now", label: "🟢 Available Now" },
        { value: "today", label: "📅 Today" },
        { value: "weekend", label: "🎉 This Weekend" },
        { value: "week", label: "🗓️ This Week" },
        { value: "flexible", label: "🔄 Flexible Schedule" },
    ];

    return (
        <>
            <section className="luxury-hero-section position-relative overflow-hidden py-5">
                {/* Animated Background Images */}
                <AnimatePresence mode="wait">
                    <motion.div
                        key={currentImageIndex}
                        initial={{ opacity: 0, scale: 1.1 }}
                        animate={{ opacity: 1, scale: 1 }}
                        exit={{ opacity: 0, scale: 1.1 }}
                        transition={{ duration: 1.2, ease: "easeInOut" }}
                        className="hero-background"
                        style={{
                            backgroundImage: `url(${backgroundImages[currentImageIndex]})`,
                        }}
                    />
                </AnimatePresence>

                {/* Gradient Overlays */}
                <div className="gradient-overlay-primary" />
                <div className="gradient-overlay-secondary" />

                {/* Animated Particles */}
                <div className="hero-particles">
                    {[...Array(20)].map((_, i) => (
                        <motion.div
                            key={i}
                            className="particle"
                            initial={{ opacity: 0, y: -20 }}
                            animate={{
                                opacity: [0, 1, 0],
                                y: [0, -100],
                                x: Math.sin(i * 0.5) * 50,
                            }}
                            transition={{
                                duration: 3 + Math.random() * 2,
                                repeat: Infinity,
                                delay: i * 0.1,
                            }}
                        />
                    ))}
                </div>

                <Container className="position-relative z-2">
                    <Row className="align-items-center min-vh-100 py-5 py-lg-0">
                        {/* Hero Content */}
                        <Col lg={6} className="mb-5 mb-lg-0">
                            <motion.div
                                initial={{ opacity: 0, y: 30 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.8, delay: 0.2 }}
                                className="hero-content text-white pe-lg-5"
                            >
                                {/* Badge */}
                                <div className="hero-badge d-inline-flex align-items-center gap-2 mb-4 p-2 rounded-pill bg-dark bg-opacity-50">
                                    <span className="pulse-dot"></span>
                                    <span className="small fw-medium">
                                        Kenya's #1 Dating Platform
                                    </span>
                                </div>

                                {/* Main Heading */}
                                <h1 className="hero-title display-3 fw-bold mb-3">
                                    Discover Your{" "}
                                    <span className="text-gold-gradient">
                                        Ideal
                                    </span>{" "}
                                    Match in Kenya
                                </h1>

                                {/* Subtitle */}
                                <p className="hero-subtitle lead mb-4 opacity-90">
                                    Connect with authentic, verified singles
                                    ready for meaningful relationships.
                                    Experience luxury dating with discretion and
                                    elegance.
                                </p>

                                {/* Stats */}
                                <div className="hero-stats border rounded border-dark d-flex flex-wrap gap-4 mb-4">
                                    <div className="stat-item">
                                        <div className="stat-number display-6 fw-bold">
                                            10K+
                                        </div>
                                        <div className="stat-label small opacity-75">
                                            Active Members
                                        </div>
                                    </div>
                                    <div className="stat-item">
                                        <div className="stat-number display-6 fw-bold">
                                            98%
                                        </div>
                                        <div className="stat-label small opacity-75">
                                            Verified Profiles
                                        </div>
                                    </div>
                                    <div className="stat-item">
                                        <div className="stat-number display-6 fw-bold">
                                            {counties?.length || 0}
                                        </div>
                                        <div className="stat-label small opacity-75">
                                            Counties Covered
                                        </div>
                                    </div>
                                </div>

                                {/* CTA Buttons */}
                                <div className="hero-cta d-flex flex-wrap gap-3">
                                    <Link
                                        href={route("register")}
                                        className="btn btn-gold btn-lg d-flex align-items-center gap-2 px-4 py-3 fw-semibold"
                                    >
                                        <i className="bi bi-star-fill"></i>
                                        Join Free Today
                                    </Link>
                                    <Link
                                        href="#how-it-works"
                                        className="btn btn-outline-light btn-lg d-flex align-items-center gap-2 px-4 py-3 fw-semibold"
                                    >
                                        <i className="bi bi-play-circle"></i>
                                        See How It Works
                                    </Link>
                                </div>

                                {/* Trust Indicators */}
                                <div className="trust-indicators mt-5 pt-3 border-top border-dark">
                                    <div className="d-flex align-items-center gap-3">
                                        <div className="d-flex align-items-center gap-1">
                                            <i className="bi bi-shield-check text-success"></i>
                                            <small className="opacity-75">
                                                Secure & Verified
                                            </small>
                                        </div>
                                        <div className="d-flex align-items-center gap-1">
                                            <i className="bi bi-lock-fill text-gold"></i>
                                            <small className="opacity-75">
                                                Discreet Service
                                            </small>
                                        </div>
                                        <div className="d-flex align-items-center gap-1">
                                            <i className="bi bi-check-circle-fill text-info"></i>
                                            <small className="opacity-75">
                                                24/7 Support
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </motion.div>
                        </Col>

                        {/* Search Form */}
                        <Col lg={6}>
                            <motion.div
                                initial={{ opacity: 0, x: 50 }}
                                animate={{ opacity: 1, x: 0 }}
                                transition={{ duration: 0.8, delay: 0.4 }}
                                className="search-card"
                            >
                                {/* Card Header */}
                                <div className="search-card-header text-center p-4">
                                    <h2 className="search-title mb-1">
                                        <span className="text-gold">
                                            Find Your
                                        </span>{" "}
                                        Ideal Partner
                                    </h2>
                                    <p className="search-subtitle opacity-75 mb-0">
                                        Serious connections start here
                                    </p>
                                </div>

                                {/* Search Form */}
                                <div className="search-card-body p-4">
                                    <Form
                                        onSubmit={handleSubmit}
                                        className="search-form"
                                    >
                                        {/* Location */}
                                        <div className="form-group mb-4">
                                            <label
                                                htmlFor="location"
                                                className="form-label d-flex align-items-center gap-2 mb-2"
                                            >
                                                <i className="bi bi-geo-alt-fill text-gold"></i>
                                                <span className="fw-medium">
                                                    Location
                                                </span>
                                            </label>
                                            <div className="input-group">
                                                <select
                                                    name="location"
                                                    id="location"
                                                    className="form-select search-select"
                                                    value={searchData.location}
                                                    onChange={handleChange}
                                                    required
                                                >
                                                    <option value="">
                                                        Select Your County
                                                    </option>
                                                    {counties?.map((county) => (
                                                        <option
                                                            key={county?.name.toLowerCase()}
                                                            value={county?.name.toLowerCase()}
                                                        >
                                                            📍 {county?.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                        </div>

                                        {/* Service Type */}
                                        <div className="form-group mb-4">
                                            <label
                                                htmlFor="service"
                                                className="form-label d-flex align-items-center gap-2 mb-2"
                                            >
                                                <i className="bi bi-heart-fill text-gold"></i>
                                                <span className="fw-medium">
                                                    Looking For
                                                </span>
                                            </label>
                                            <select
                                                name="service"
                                                id="service"
                                                className="form-select search-select"
                                                value={searchData.service}
                                                onChange={handleChange}
                                            >
                                                <option value="">
                                                    Any Type of Connection
                                                </option>
                                                {serviceOptions.map(
                                                    (option) => (
                                                        <option
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </option>
                                                    )
                                                )}
                                            </select>
                                        </div>

                                        {/* Age Range */}
                                        <div className="form-group mb-4">
                                            <label
                                                htmlFor="ageRange"
                                                className="form-label d-flex align-items-center gap-2 mb-2"
                                            >
                                                <i className="bi bi-person-fill text-gold"></i>
                                                <span className="fw-medium">
                                                    Age Preference
                                                </span>
                                            </label>
                                            <div className="age-slider-container">
                                                <select
                                                    name="ageRange"
                                                    id="ageRange"
                                                    className="form-select search-select"
                                                    value={searchData.ageRange}
                                                    onChange={handleChange}
                                                >
                                                    <option value="">
                                                        Any Age Range
                                                    </option>
                                                    {ageOptions.map(
                                                        (option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        )
                                                    )}
                                                </select>
                                            </div>
                                        </div>

                                        {/* Availability */}
                                        <div className="form-group mb-4">
                                            <label
                                                htmlFor="availability"
                                                className="form-label d-flex align-items-center gap-2 mb-2"
                                            >
                                                <i className="bi bi-clock-fill text-gold"></i>
                                                <span className="fw-medium">
                                                    Availability
                                                </span>
                                            </label>
                                            <select
                                                name="availability"
                                                id="availability"
                                                className="form-select search-select"
                                                value={searchData.availability}
                                                onChange={handleChange}
                                            >
                                                {availabilityOptions.map(
                                                    (option) => (
                                                        <option
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </option>
                                                    )
                                                )}
                                            </select>
                                        </div>

                                        {/* Search Button */}
                                        <button
                                            type="submit"
                                            className="btn btn-search w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2"
                                        >
                                            <i className="bi bi-search"></i>
                                            Find Your Match Now
                                            <motion.span
                                                animate={{ x: [0, 5, 0] }}
                                                transition={{
                                                    duration: 1.5,
                                                    repeat: Infinity,
                                                }}
                                            >
                                                →
                                            </motion.span>
                                        </button>

                                        {/* Quick Filters */}
                                        <div className="quick-filters mt-4 pt-3 border-top border-secondary">
                                            <p className="small text-center mb-3 opacity-75">
                                                Popular Searches:
                                            </p>
                                            <div className="d-flex flex-wrap gap-2 justify-content-center">
                                                {[
                                                    "Nairobi",
                                                    "Mombasa",
                                                    "Dating",
                                                    "30-35",
                                                    "Weekend",
                                                ].map((filter) => (
                                                    <button
                                                        key={filter}
                                                        type="button"
                                                        className="btn btn-outline-gold btn-sm px-3"
                                                        onClick={() => {
                                                            if (
                                                                [
                                                                    "Nairobi",
                                                                    "Mombasa",
                                                                ].includes(
                                                                    filter
                                                                )
                                                            ) {
                                                                setSearchData(
                                                                    (prev) => ({
                                                                        ...prev,
                                                                        location:
                                                                            filter.toLowerCase(),
                                                                    })
                                                                );
                                                            } else if (
                                                                filter ===
                                                                "Dating"
                                                            ) {
                                                                setSearchData(
                                                                    (prev) => ({
                                                                        ...prev,
                                                                        service:
                                                                            "dinner",
                                                                    })
                                                                );
                                                            } else if (
                                                                filter ===
                                                                "30-35"
                                                            ) {
                                                                setSearchData(
                                                                    (prev) => ({
                                                                        ...prev,
                                                                        ageRange:
                                                                            "31-35",
                                                                    })
                                                                );
                                                            } else if (
                                                                filter ===
                                                                "Weekend"
                                                            ) {
                                                                setSearchData(
                                                                    (prev) => ({
                                                                        ...prev,
                                                                        availability:
                                                                            "weekend",
                                                                    })
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        {filter}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    </Form>
                                </div>

                                {/* Card Footer */}
                                <div className="search-card-footer text-center p-3 bg-dark bg-opacity-50">
                                    <small className="opacity-75">
                                        <i className="bi bi-info-circle me-1"></i>
                                        100% secure platform • Verified profiles
                                        only • Privacy guaranteed
                                    </small>
                                </div>
                            </motion.div>
                        </Col>
                    </Row>
                </Container>
            </section>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 150">
                <g transform="scale(1, -1) translate(0, -150)">
                    <path
                        fill="#000000"
                        fillOpacity="0.30"
                        d="M 0 110 L 0 67.68944362998809 C 60 67.68944362998809 60 104.25354339332269 120 104.25354339332269 C 180 104.25354339332269 180 110.16891326736884 240 110.16891326736884 C 300 110.16891326736884 300 64.53081561802114 360 64.53081561802114 C 420 64.53081561802114 420 81.24982762659859 480 81.24982762659859 C 540 81.24982762659859 540 49.37733784680336 600 49.37733784680336 C 660 49.37733784680336 660 67.96920696424344 720 67.96920696424344 C 780 67.96920696424344 780 86.14175105444362 840 86.14175105444362 C 900 86.14175105444362 900 122.30635809926723 960 122.30635809926723 C 1020 122.30635809926723 1020 87.94007508757191 1080 87.94007508757191 C 1140 87.94007508757191 1140 90.49475177461356 1200 90.49475177461356 C 1260 90.49475177461356 1260 45.90728741388152 1320 45.90728741388152 C 1380 45.90728741388152 1380 74.50487998756805 1440 74.50487998756805 C 1440 74.50487998756805 1440 320 1440 320 L 1440 320 L 0 320 Z"
                    ></path>
                    <path
                        fill="#000000"
                        fillOpacity="0.42"
                        d="M 0 150 L 0 153.98171422352615 C 60 153.98171422352615 60 129.94654282163782 120 129.94654282163782 C 180 129.94654282163782 180 103.17004408109219 240 103.17004408109219 C 300 103.17004408109219 300 124.00035148446553 360 124.00035148446553 C 420 124.00035148446553 420 111.46849345488977 480 111.46849345488977 C 540 111.46849345488977 540 108.81980304406092 600 108.81980304406092 C 660 108.81980304406092 660 152.96158154268258 720 152.96158154268258 C 780 152.96158154268258 780 135.0228811861777 840 135.0228811861777 C 900 135.0228811861777 900 114.95678631177485 960 114.95678631177485 C 1020 114.95678631177485 1020 108.85446257728329 1080 108.85446257728329 C 1140 108.85446257728329 1140 88.72800420190974 1200 88.72800420190974 C 1260 88.72800420190974 1260 105.94886105000248 1320 105.94886105000248 C 1380 105.94886105000248 1380 152.4593654587821 1440 152.4593654587821 C 1440 152.4593654587821 1440 320 1440 320 L 1440 320 L 0 320 Z"
                    ></path>
                    <path
                        fill="#000000"
                        fillOpacity="0.55"
                        d="M 0 190 L 0 189.37569996457813 C 60 189.37569996457813 60 162.5718066782732 120 162.5718066782732 C 180 162.5718066782732 180 148.27531578314012 240 148.27531578314012 C 300 148.27531578314012 300 143.28425590171196 360 143.28425590171196 C 420 143.28425590171196 420 172.20511896227129 480 172.20511896227129 C 540 172.20511896227129 540 170.12628955110475 600 170.12628955110475 C 660 170.12628955110475 660 178.85336955697284 720 178.85336955697284 C 780 178.85336955697284 780 149.6848481119895 840 149.6848481119895 C 900 149.6848481119895 900 168.2867574273318 960 168.2867574273318 C 1020 168.2867574273318 1020 129.42827465899205 1080 129.42827465899205 C 1140 129.42827465899205 1140 178.53648972120843 1200 178.53648972120843 C 1260 178.53648972120843 1260 164.13469522437623 1320 164.13469522437623 C 1380 164.13469522437623 1380 166.93509448348678 1440 166.93509448348678 C 1440 166.93509448348678 1440 320 1440 320 L 1440 320 L 0 320 Z"
                    ></path>
                    <path
                        fill="#000000"
                        fillOpacity="0.68"
                        d="M 0 230 L 0 205.19266138556722 C 60 205.19266138556722 60 204.2135651737779 120 204.2135651737779 C 180 204.2135651737779 180 192.97156218390614 240 192.97156218390614 C 300 192.97156218390614 300 188.35142995267267 360 188.35142995267267 C 420 188.35142995267267 420 223.91452707426313 480 223.91452707426313 C 540 223.91452707426313 540 207.98843136803924 600 207.98843136803924 C 660 207.98843136803924 660 195.76460598709662 720 195.76460598709662 C 780 195.76460598709662 780 170.80209631386464 840 170.80209631386464 C 900 170.80209631386464 900 207.59833616046996 960 207.59833616046996 C 1020 207.59833616046996 1020 179.88993680085647 1080 179.88993680085647 C 1140 179.88993680085647 1140 231.64110535595825 1200 231.64110535595825 C 1260 231.64110535595825 1260 208.68609312248637 1320 208.68609312248637 C 1380 208.68609312248637 1380 236.01872980321951 1440 236.01872980321951 C 1440 236.01872980321951 1440 320 1440 320 L 1440 320 L 0 320 Z"
                    ></path>
                    <path
                        fill="#000000"
                        fillOpacity="1.00"
                        d="M 0 270 L 0 247.98986437995228 C 60 247.98986437995228 60 223.0240418738906 120 223.0240418738906 C 180 223.0240418738906 180 241.28472979171795 240 241.28472979171795 C 300 241.28472979171795 300 282.303785733626 360 282.303785733626 C 420 282.303785733626 420 250.64964031625067 480 250.64964031625067 C 540 250.64964031625067 540 248.71254036760536 600 248.71254036760536 C 660 248.71254036760536 660 235.60128762751486 720 235.60128762751486 C 780 235.60128762751486 780 237.89102795219839 840 237.89102795219839 C 900 237.89102795219839 900 209.9167854787506 960 209.9167854787506 C 1020 209.9167854787506 1020 246.01905773911454 1080 246.01905773911454 C 1140 246.01905773911454 1140 257.42679102018786 1200 257.42679102018786 C 1260 257.42679102018786 1260 252.1440864166396 1320 252.1440864166396 C 1380 252.1440864166396 1380 257.58587542745744 1440 257.58587542745744 C 1440 257.58587542745744 1440 320 1440 320 L 1440 320 L 0 320 Z"
                    ></path>
                </g>
            </svg>
        </>
    );
}

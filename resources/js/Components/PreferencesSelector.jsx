import React, { useState } from 'react';
import { Container, Row, Col } from 'react-bootstrap';

export default function PreferencesSelector() {
    const [formData, setFormData] = useState({
        currentLocation: '',
        yourGender: '',
        preferredGender: '',
        budgetRange: [300, 1000]
    });

    const locations = [
        'New York', 'London', 'Paris', 'Dubai',
        'Tokyo', 'Los Angeles', 'Miami', 'Singapore'
    ];

    const genders = ['Male', 'Female', 'Non-binary', 'Transgender', 'No preference'];

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleBudgetChange = (e) => {
        setFormData(prev => ({
            ...prev,
            budgetRange: [parseInt(e.target.value), prev.budgetRange[1]]
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log('Submitted preferences:', formData);
        // Add your form submission logic here
    };

    return (
        <Container fluid className="preferences-selector py-5">
            <Row className="justify-content-center mb-4">
                <Col lg={8} className="text-center">
                    <h2 className="section-title">
                        <span className="gold-text">Refine</span> Your Search
                    </h2>
                    <div className="divider"></div>
                    <p className="section-subtitle">
                        Select your preferences to find your perfect match
                    </p>
                </Col>
            </Row>

            <Row className="justify-content-center mx-2">
                <Col lg={8} className='auth-container'>
                    <form onSubmit={handleSubmit} className="preferences-form">
                        {/* Location Selector */}
                        <div className="form-group mb-4">
                            <label className="form-label">Your Current Location</label>
                            <div className="select-wrapper">
                                <select
                                    name="currentLocation"
                                    value={formData.currentLocation}
                                    onChange={handleChange}
                                    className="form-control"
                                    required
                                >
                                    <option value="">Select a location</option>
                                    {locations.map((location, index) => (
                                        <option key={index} value={location}>{location}</option>
                                    ))}
                                </select>
                                <i className="fas fa-map-marker-alt select-icon"></i>
                            </div>
                        </div>

                        {/* Gender Preferences */}
                        <Row>
                            <Col md={6} className="mb-4">
                                <label className="form-label">Your Gender</label>
                                <div className="radio-group">
                                    {genders.map((gender, index) => (
                                        <div key={index} className="radio-option">
                                            <input
                                                type="radio"
                                                id={`yourGender-${index}`}
                                                name="yourGender"
                                                value={gender}
                                                checked={formData.yourGender === gender}
                                                onChange={handleChange}
                                                required
                                            />
                                            <label htmlFor={`yourGender-${index}`}>
                                                <span className="radio-button"></span>
                                                {gender}
                                            </label>
                                        </div>
                                    ))}
                                </div>
                            </Col>

                            <Col md={6} className="mb-4">
                                <label className="form-label">Preferred Companion Gender</label>
                                <div className="radio-group">
                                    {genders.map((gender, index) => (
                                        <div key={index} className="radio-option">
                                            <input
                                                type="radio"
                                                id={`preferredGender-${index}`}
                                                name="preferredGender"
                                                value={gender}
                                                checked={formData.preferredGender === gender}
                                                onChange={handleChange}
                                                required
                                            />
                                            <label htmlFor={`preferredGender-${index}`}>
                                                <span className="radio-button"></span>
                                                {gender}
                                            </label>
                                        </div>
                                    ))}
                                </div>
                            </Col>
                        </Row>

                        {/* Budget Range */}
                        <div className="form-group mb-5">
                            <label className="form-label">
                                Budget Range:
                                <span className="budget-value"> Ksh {formData.budgetRange[0]} - Ksh {formData.budgetRange[1]}</span>
                            </label>
                            <input
                                type="range"
                                className="form-range"
                                min="100"
                                max="2000"
                                step="100"
                                value={formData.budgetRange[0]}
                                onChange={handleBudgetChange}
                            />
                            <div className="d-flex justify-content-between">
                                <small>Ksh 100</small>
                                <small>Ksh 2000</small>
                            </div>
                        </div>

                        {/* Submit Button */}
                        <div className="text-center">
                            <button type="submit" className="submit-button">
                                Find My Perfect Match
                                <i className="fas fa-search ms-2"></i>
                            </button>
                        </div>
                    </form>
                </Col>
            </Row>
        </Container>
    );
}
// useLocation.js
import { useState, useEffect } from 'react';

export default function useLocation() {
    const [location, setLocation] = useState('Nairobi');
    const [userLocation, setUserLocation] = useState(null);
    const [fullLocationData, setFullLocationData] = useState(null);
    const [locationError, setLocationError] = useState(null);
    const [isLoadingLocation, setIsLoadingLocation] = useState(true);

    const reverseGeocode = async (lat, lng) => {
        try {
            // Using Nominatim (OpenStreetMap) - free but requires attribution
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1`
            );

            if (!response.ok) {
                throw new Error('Failed to fetch location data');
            }

            const data = await response.json();

            // Extract detailed location information
            const address = data.address || {};
            const locationDetails = {
                displayName: data.display_name ||
                    [address.city, address.town, address.village, address.county, address.state].find(Boolean) ||
                    'Your Location',
                city: address.city || address.town || address.village,
                county: address.county,
                state: address.state,
                country: address.country,
                postcode: address.postcode,
                fullAddress: data.display_name,
                latitude: lat,
                longitude: lng
            };

            return locationDetails;
        } catch (error) {
            console.error('Reverse geocoding error:', error);
            return {
                displayName: 'Your Location',
                latitude: lat,
                longitude: lng
            };
        }
    };

    useEffect(() => {
        // Get user location from localStorage
        const userLatitude = localStorage.getItem('userLatitude');
        const userLongitude = localStorage.getItem('userLongitude');

        if (userLatitude && userLongitude) {
            reverseGeocode(userLatitude, userLongitude)
                .then(locationData => {
                    setUserLocation(locationData.displayName);
                    setFullLocationData(locationData);
                    setLocation(locationData.displayName);
                    setIsLoadingLocation(false);
                })
                .catch(err => {
                    console.error('Error getting location name:', err);
                    setLocationError('Failed to get location details');
                    setIsLoadingLocation(false);
                });
        } else {
            setLocationError('Location not available. Please enable location services.');
            setIsLoadingLocation(false);
        }
    }, []);

    return {
        location,
        userLocation,
        fullLocationData,
        locationError,
        isLoadingLocation,
        reverseGeocode
    };
}
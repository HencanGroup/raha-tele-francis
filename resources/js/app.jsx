import 'bootstrap-icons/font/bootstrap-icons.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'react-toastify/dist/ReactToastify.css';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { useEffect } from 'react';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Component to handle location detection and storage
const LocationHandler = ({ children }) => {
    useEffect(() => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;

                    // Store in localStorage
                    localStorage.setItem('userLatitude', latitude);
                    localStorage.setItem('userLongitude', longitude);

                    // You can also use cookies if needed
                    document.cookie = `userLatitude=${latitude}; path=/`;
                    document.cookie = `userLongitude=${longitude}; path=/`;

                    console.log('Location stored:', { latitude, longitude });
                },
                (error) => {
                    console.error('Error getting location:', error);
                    // You might want to set default values or handle the error
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        } else {
            console.error('Geolocation is not supported by this browser.');
        }
    }, []);

    return children;
};

createInertiaApp({
    title: (title) => `${title} - Raha Tele`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <LocationHandler>
                <App {...props} />
            </LocationHandler>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
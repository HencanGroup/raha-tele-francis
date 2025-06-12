import EscortsNearMe from '@/Components/EscortsNearMe';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { Card, Container } from 'react-bootstrap';

export default function SinglesNearMe() {

    return (
        <AppLayout>
            <Head title="Singles Near Me" />

            <EscortsNearMe />
        </AppLayout>
    );
}

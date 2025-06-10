import AppLayout from '@/Layouts/AppLayout';
import Plans from '@/Components/Plans';
import { Head } from '@inertiajs/react';

export default function Plan() {

    return (
        <AppLayout>
            <Head title="Subscription Plans" />

            <Plans />
        </AppLayout>
    );
}

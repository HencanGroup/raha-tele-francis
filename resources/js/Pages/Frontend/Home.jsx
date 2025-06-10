import AppLayout from '@/Layouts/AppLayout';
import EscortCarousel from '@/Components/EscortCarousel';
import MemberCounter from '@/Components/MemberCounter';
import NewlyMembers from '@/Components/NewlyMembers';
import PreferencesSelector from '@/Components/PreferencesSelector';
import SuccessStories from '@/Components/SuccessStories';
import { Head } from '@inertiajs/react';

export default function Home({ auth }) {

    return (
        <AppLayout>
            <Head title="Home" />

            <EscortCarousel />

            <NewlyMembers />

            <MemberCounter />

            <SuccessStories />

            <PreferencesSelector />
        </AppLayout>
    );
}

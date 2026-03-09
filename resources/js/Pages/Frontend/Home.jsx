import AppLayout from "@/Layouts/AppLayout";
import HeroSection from "@/Components/Partials/HeroSection";
import EscortsList from "@/Components/Partials/EscortsList";

import { Head } from "@inertiajs/react";

export default function Home({ auth }) {
    return (
        <AppLayout>
            <Head title="Home" />

            <HeroSection />

            <div className="mb-4">
                <EscortsList />
            </div>

            {/* <NewlyMembers /> */}

            {/* <MemberCounter /> */}

            {/* <SuccessStories /> */}

            {/* <PreferencesSelector /> */}
        </AppLayout>
    );
}

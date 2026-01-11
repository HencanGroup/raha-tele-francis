import AppLayout from "@/Layouts/AppLayout";
import HeroSection from "@/Components/Pages/HeroSection";
import EscortsList from "@/Components/Pages/EscortsList";

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

import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { Card, Container } from 'react-bootstrap';

export default function SinglesNearMe() {

    return (
        <AppLayout>
            <Head title="Singles Near Me" />

            <Container className='py-5'>
                <Card>
                    <Card.Body className='text-center p-5'>
                        We are currently working on this page, content will be available once through
                    </Card.Body>
                </Card>
            </Container>
        </AppLayout>
    );
}

import { Head } from "@inertiajs/react";
import {
    Container, Card, Button, Row, Col, Image, Badge, ProgressBar,
    Tab, Tabs, ListGroup, Modal
} from "react-bootstrap";
import AppLayout from "@/Layouts/AppLayout";
import { useState } from "react";
import { FaEdit, FaStar, FaMapMarkerAlt, FaPhone, FaEnvelope, FaBirthdayCake, FaVenusMars, FaUserCheck } from "react-icons/fa";
import { IoMdPhotos } from "react-icons/io";
import { MdVerified } from "react-icons/md";

export default function ProfileShow({ user, auth }) {
    const [showGallery, setShowGallery] = useState(false);
    const [activeTab, setActiveTab] = useState('about');

    const isOwnProfile = auth.user?.id === user.id;
    const verificationBadge = user.is_verified ? (
        <Badge bg="primary" className="d-flex align-items-center gap-1">
            <MdVerified /> Verified
        </Badge>
    ) : (
        <Badge bg="secondary">Not Verified</Badge>
    );

    return (
        <AppLayout user={auth.user}>
            <Head title={`${user.name}'s Profile`} />

            <Container className="profile-container py-5">
                <Row>
                    <Col md={12} className="profile-bg mb-4">
                        <Card>
                            <Card.Body style={{ minHeight: "200px" }}></Card.Body>
                        </Card>

                        <Row className="profile-show-info">
                            <Col md={3} className="mb-3">
                                <Image
                                    src={`/storage/${user.profile_picture}`}
                                    roundedCircle
                                    width={150}
                                    height={150}
                                    className="border border-4 border-white shadow"
                                />
                            </Col>
                            <Col md={9}>
                                <div className="d-flex align-items-center gap-3 mb-2">
                                    <h2 className="mb-0 fw-bold text-capitalize">{user.name}</h2>
                                </div>

                                <div className="d-flex align-items-center gap-3 mb-2">
                                    {verificationBadge}
                                    {user.hasActiveSubscription && (
                                        <Badge bg="success">Premium</Badge>
                                    )}
                                </div>

                                <div className="d-flex align-items-center gap-3 text-white-50">
                                    <div className="d-flex align-items-center gap-1">
                                        <FaMapMarkerAlt size={14} />
                                        <span>{user.location || 'Location not set'}</span>
                                    </div>
                                    <div className="d-flex align-items-center gap-1">
                                        <FaStar size={14} className="text-warning" />
                                        <span>{user.rating?.toFixed(1) || '0.0'} ({user.review_count} reviews)</span>
                                    </div>
                                </div>
                            </Col>
                        </Row>
                    </Col>


                    <Col md={4}>
                        {/* Sidebar Info */}
                        <Card className="mb-4 shadow-sm">
                            <Card.Body>
                                <h5 className="fw-bold mb-4">Details</h5>

                                <ListGroup variant="flush">
                                    <ListGroup.Item className="d-flex align-items-center gap-3 py-3 bg-transparent text-white text-capitalize">
                                        <div className="text-white-50">
                                            <FaVenusMars size={18} />
                                        </div>
                                        <div>
                                            <small className="text-white-50 d-block">Gender</small>
                                            <div>
                                                {user.gender || 'Not specified'}
                                                {user.searching_for && (
                                                    <span className="text-white-50"> • Looking for {user.searching_for}</span>
                                                )}
                                            </div>
                                        </div>
                                    </ListGroup.Item>

                                    <ListGroup.Item className="d-flex align-items-center gap-3 py-3 bg-transparent text-white text-capitalize">
                                        <div className="text-white-50">
                                            <FaBirthdayCake size={18} />
                                        </div>
                                        <div>
                                            <small className="text-white-50 d-block">Age</small>
                                            <div>{user.age || 'Not specified'}</div>
                                        </div>
                                    </ListGroup.Item>

                                    <ListGroup.Item className="d-flex align-items-center gap-3 py-3 bg-transparent text-white text-capitalize">
                                        <div className="text-white-50">
                                            <FaUserCheck size={18} />
                                        </div>
                                        <div>
                                            <small className="text-white-50 d-block">Status</small>
                                            <div>
                                                <Badge bg={
                                                    user.status === 'active' ? 'success' :
                                                        user.status === 'pending' ? 'warning' :
                                                            user.status === 'suspended' ? 'danger' :
                                                                'secondary'
                                                }>
                                                    {user.status}
                                                </Badge>
                                            </div>
                                        </div>
                                    </ListGroup.Item>

                                    {isOwnProfile && (
                                        <>
                                            <ListGroup.Item className="d-flex align-items-center gap-3 py-3 bg-transparent text-white text-capitalize">
                                                <div className="text-white-50">
                                                    <FaEnvelope size={18} />
                                                </div>
                                                <div>
                                                    <small className="text-white-50 d-block">Email</small>
                                                    <div>{user.email}</div>
                                                </div>
                                            </ListGroup.Item>

                                            {user.phone_number && (
                                                <ListGroup.Item className="d-flex align-items-center gap-3 py-3 bg-transparent text-white text-capitalize">
                                                    <div className="text-white-50">
                                                        <FaPhone size={18} />
                                                    </div>
                                                    <div>
                                                        <small className="text-white-50 d-block">Phone</small>
                                                        <div className="d-flex align-items-center gap-2">
                                                            {user.phone_number}
                                                            {user.phone_verified ? (
                                                                <Badge bg="success" className="d-flex align-items-center gap-1">
                                                                    <MdVerified size={14} /> Verified
                                                                </Badge>
                                                            ) : (
                                                                <Badge bg="warning">Unverified</Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                </ListGroup.Item>
                                            )}
                                        </>
                                    )}
                                </ListGroup>

                                {user.latitude && user.longitude && (
                                    <div className="mt-4">
                                        <h6 className="fw-bold mb-3">Location</h6>
                                        <div
                                            className="rounded-3 bg-light"
                                            style={{
                                                height: '150px',
                                                background: 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)'
                                            }}
                                        ></div>
                                    </div>
                                )}
                            </Card.Body>
                        </Card>

                        {/* Gallery Preview */}
                        {user.gallery?.length > 0 && (
                            <Card className="shadow-sm">
                                <Card.Body>
                                    <div className="d-flex justify-content-between align-items-center mb-3">
                                        <h5 className="fw-bold mb-0">Gallery</h5>
                                        <Button
                                            variant="link"
                                            size="sm"
                                            onClick={() => setShowGallery(true)}
                                            className="text-decoration-none d-flex align-items-center gap-1"
                                        >
                                            <IoMdPhotos /> View All
                                        </Button>
                                    </div>

                                    <div className="row g-2">
                                        {user.gallery.slice(0, 4).map((img, index) => (
                                            <div key={index} className="col-6">
                                                <Image
                                                    src={img}
                                                    fluid
                                                    rounded
                                                    className="w-100 h-100 object-fit-cover"
                                                    style={{ height: '100px' }}
                                                />
                                            </div>
                                        ))}
                                    </div>
                                </Card.Body>
                            </Card>
                        )}
                    </Col>

                    <Col md={8}>
                        {/* Profile Tabs */}
                        <Card className="shadow-sm mb-4">
                            <Card.Body>
                                <Tabs
                                    activeKey={activeTab}
                                    onSelect={(k) => setActiveTab(k)}
                                    className="mb-4"
                                >
                                    <Tab eventKey="about" title="About">
                                        <div className="mt-3">
                                            <h5 className="fw-bold mb-3">Bio</h5>
                                            <p className="text-white-50">
                                                {user.bio || 'No bio provided yet.'}
                                            </p>

                                            <h5 className="fw-bold mb-3 mt-4">Looking For</h5>
                                            <p className="text-white-50">
                                                {user.searching_for ? `Looking for ${user.searching_for}` : 'Not specified'}
                                            </p>
                                        </div>
                                    </Tab>

                                    <Tab eventKey="reviews" title={`Reviews (${user.review_count})`}>
                                        <div className="mt-3">
                                            {user.review_count > 0 ? (
                                                <>
                                                    <div className="d-flex align-items-center gap-3 mb-4">
                                                        <div className="text-center">
                                                            <h1 className="mb-0 fw-bold">{user.rating?.toFixed(1) || '0.0'}</h1>
                                                            <div className="d-flex justify-content-center text-warning">
                                                                {[...Array(5)].map((_, i) => (
                                                                    <FaStar
                                                                        key={i}
                                                                        size={16}
                                                                        className={i < Math.floor(user.rating) ? 'fill' : 'text-white-50'}
                                                                    />
                                                                ))}
                                                            </div>
                                                            <small className="text-white-50">{user.review_count} reviews</small>
                                                        </div>

                                                        <div className="flex-grow-1">
                                                            {[5, 4, 3, 2, 1].map((star) => (
                                                                <div key={star} className="d-flex align-items-center gap-2 mb-2">
                                                                    <small className="text-white-50" style={{ width: '20px' }}>{star}</small>
                                                                    <ProgressBar
                                                                        now={Math.random() * 100} // Replace with actual data
                                                                        variant="warning"
                                                                        style={{ height: '8px', flexGrow: 1 }}
                                                                    />
                                                                    <small className="text-white-50">{(Math.random() * 50).toFixed(0)}%</small>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>

                                                    <div className="border-top pt-3">
                                                        {/* Sample review - replace with actual reviews */}
                                                        <div className="mb-4">
                                                            <div className="d-flex justify-content-between mb-2">
                                                                <div className="d-flex align-items-center gap-2">
                                                                    <Image
                                                                        src="/images/default-avatar.jpg"
                                                                        roundedCircle
                                                                        width={40}
                                                                        height={40}
                                                                    />
                                                                    <div>
                                                                        <h6 className="mb-0">John Doe</h6>
                                                                        <div className="d-flex text-warning">
                                                                            {[...Array(5)].map((_, i) => (
                                                                                <FaStar key={i} size={14} className={i < 4 ? 'fill' : 'text-white-50'} />
                                                                            ))}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <small className="text-white-50">2 weeks ago</small>
                                                            </div>
                                                            <p className="mb-0">Great experience with {user.name}. Very professional and friendly!</p>
                                                        </div>
                                                    </div>
                                                </>
                                            ) : (
                                                <div className="text-center py-4">
                                                    <h5 className="text-white-50">No reviews yet</h5>
                                                    <p>Be the first to review this profile</p>
                                                    {!isOwnProfile && (
                                                        <Button variant="outline-primary">Write a Review</Button>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </Tab>

                                    <Tab eventKey="verification" title="Verification">
                                        <div className="mt-3">
                                            <h5 className="fw-bold mb-3">Verification Status</h5>

                                            <ListGroup variant="flush">
                                                <ListGroup.Item className="d-flex align-items-center justify-content-between gap-3 py-3 bg-transparent text-white text-capitalize">
                                                    <div className="d-flex align-items-center gap-2">
                                                        <div className={`p-2 py-1 rounded-circle bg-${user.email_verified_at ? 'success' : 'light'}`}>
                                                            <FaEnvelope className={user.email_verified_at ? 'text-white' : 'text-white-50'} />
                                                        </div>
                                                        <span>Email Verification</span>
                                                    </div>
                                                    <Badge bg={user.email_verified_at ? 'success' : 'secondary'}>
                                                        {user.email_verified_at ? 'Verified' : 'Pending'}
                                                    </Badge>
                                                </ListGroup.Item>

                                                <ListGroup.Item className="d-flex align-items-center justify-content-between gap-3 py-3 bg-transparent text-white text-capitalize">
                                                    <div className="d-flex align-items-center gap-2">
                                                        <div className={`p-2 py-1 rounded-circle bg-${user.phone_verified ? 'success' : 'secondary'}`}>
                                                            <FaPhone className={user.phone_verified ? 'text-white' : 'text-white-50'} />
                                                        </div>
                                                        <span>Phone Verification</span>
                                                    </div>
                                                    <Badge bg={user.phone_verified ? 'success' : 'secondary'}>
                                                        {user.phone_verified ? 'Verified' : user.phone_number ? 'Pending' : 'Not Added'}
                                                    </Badge>
                                                </ListGroup.Item>

                                                <ListGroup.Item className="d-flex align-items-center justify-content-between gap-3 py-3 bg-transparent text-white text-capitalize">
                                                    <div className="d-flex align-items-center gap-2">
                                                        <div className={`p-2 py-1 rounded-circle bg-${user.is_verified ? 'success' : 'secondary'}`}>
                                                            <FaUserCheck className={user.is_verified ? 'text-white' : 'text-white-50'} />
                                                        </div>
                                                        <span>ID Verification</span>
                                                    </div>
                                                    <Badge bg={user.is_verified ? 'success' : 'secondary'}>
                                                        {user.is_verified ? 'Verified' : 'Pending'}
                                                    </Badge>
                                                </ListGroup.Item>
                                            </ListGroup>
                                        </div>
                                    </Tab>
                                </Tabs>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Container>
        </AppLayout>
    );
}
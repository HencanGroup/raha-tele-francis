import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { Container, Row, Col, Card } from 'react-bootstrap';
import {
    PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis,
    CartesianGrid, Tooltip, Legend, ResponsiveContainer, LineChart, Line
} from 'recharts';

// Color scheme for charts
const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

export default function Dashboard({ auth, metrics, charts }) {
    // Prepare data for charts
    const genderData = Object.entries(charts.gender_distribution).map(([name, value]) => ({ name, value }));
    const ageData = Object.entries(charts.age_distribution).map(([name, value]) => ({ name, value }));
    const statusData = Object.entries(charts.status_distribution).map(([name, value]) => ({ name, value }));
    const verificationData = Object.entries(charts.verification_status).map(([name, value]) => ({ name, value }));
    const subscriptionData = Object.entries(charts.subscription_stats).map(([name, value]) => ({
        name: name.split('_').join(' '),
        value
    }));
    const locationData = charts.location_distribution.map(item => ({
        name: item.location,
        value: item.count
    }));

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <Container className='py-5'>
                <div className="text-start">
                    <h2 className="fw-bold">User Analytics Dashboard</h2>
                    <p className="text-white-50 text-capitalize">Welcome {auth?.user?.name}!</p>
                </div>

                <hr className='dashed-hr mb-4' />

                {/* Metrics Cards */}
                <Row className="mb-4">
                    <Col md={3} sm={6}>
                        <Card className="metric-card">
                            <Card.Body>
                                <h6 className="card-title">Total Users</h6>
                                <h2 className="mb-0">{metrics.total_users}</h2>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3} sm={6}>
                        <Card className="metric-card">
                            <Card.Body>
                                <h6 className="card-title">New This Month</h6>
                                <h2 className="mb-0">{metrics.new_users_month}</h2>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3} sm={6}>
                        <Card className="metric-card">
                            <Card.Body>
                                <h6 className="card-title">Active Users</h6>
                                <h2 className="mb-0">{metrics.active_users}</h2>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3} sm={6}>
                        <Card className="metric-card">
                            <Card.Body>
                                <h6 className="card-title">Verified Users</h6>
                                <h2 className="mb-0">{metrics.verified_users}</h2>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>

                {/* Main Charts */}
                <Row className="mb-4">
                    <Col md={6} className="mb-4">
                        <Card>
                            <Card.Body>
                                <Card.Title>User Growth</Card.Title>
                                <div style={{ height: '300px' }}>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <LineChart data={charts.monthly_signups}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="month" />
                                            <YAxis />
                                            <Tooltip />
                                            <Legend />
                                            <Line type="monotone" dataKey="count" stroke="#8884d8" activeDot={{ r: 8 }} />
                                        </LineChart>
                                    </ResponsiveContainer>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={6} className="mb-4">
                        <Card>
                            <Card.Body>
                                <Card.Title>Gender Distribution</Card.Title>
                                <div style={{ height: '300px' }}>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={genderData}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                outerRadius={80}
                                                fill="#8884d8"
                                                dataKey="value"
                                                label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                            >
                                                {genderData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>

                {/* Second Row Charts */}
                <Row className="mb-4">
                    <Col md={6} className="mb-4">
                        <Card>
                            <Card.Body>
                                <Card.Title>Age Distribution</Card.Title>
                                <div style={{ height: '300px' }}>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={ageData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="name" />
                                            <YAxis />
                                            <Tooltip />
                                            <Bar dataKey="value" fill="#8884d8" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={6} className="mb-4">
                        <Card>
                            <Card.Body>
                                <Card.Title>Account Status</Card.Title>
                                <div style={{ height: '300px' }}>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={statusData}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                outerRadius={80}
                                                fill="#8884d8"
                                                dataKey="value"
                                                label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                            >
                                                {statusData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>

                {/* Third Row Charts */}
                <Row className="mb-4">
                    <Col md={6} className="mb-4">
                        <Card>
                            <Card.Body>
                                <Card.Title>Verification Status</Card.Title>
                                <div style={{ height: '300px' }}>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={verificationData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="name" />
                                            <YAxis />
                                            <Tooltip />
                                            <Bar dataKey="value" fill="#8884d8" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={6} className="mb-4">
                        <Card>
                            <Card.Body>
                                <Card.Title>Subscription Status</Card.Title>
                                <div style={{ height: '300px' }}>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={subscriptionData}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                outerRadius={80}
                                                fill="#8884d8"
                                                dataKey="value"
                                                label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                            >
                                                {subscriptionData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>

                {/* Location Distribution */}
                <Row>
                    <Col md={12}>
                        <Card>
                            <Card.Body>
                                <Card.Title>Top Locations</Card.Title>
                                <div style={{ height: '400px' }}>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart
                                            data={locationData}
                                            layout="vertical"
                                            margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
                                        >
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis type="number" />
                                            <YAxis dataKey="name" type="category" width={150} />
                                            <Tooltip />
                                            <Bar dataKey="value" fill="#8884d8" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Container>
        </AppLayout>
    );
}
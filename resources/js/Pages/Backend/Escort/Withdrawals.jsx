import AppLayout from "@/Layouts/AppLayout";
import { Head, Link, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import {
    Container,
    Card,
    Table,
    Badge,
    Pagination,
    Spinner,
    Row,
    Col,
    Form,
    Button,
    Alert,
} from "react-bootstrap";
import { ArrowLeft, Wallet, Send } from "lucide-react";
import { ensureSessionToken } from "@/Utils/auth";
import { toast } from "react-toastify";
import xios from "@/Utils/xios";
import dayjs from "dayjs";

/**
 * Escort withdrawal page — balance display, withdrawal request form, and
 * paginated withdrawal history. Data fetched client-side from the Sanctum API.
 */
const Withdrawals = () => {
    const { auth } = usePage().props;

    const [loading, setLoading] = useState(true);
    const [balance, setBalance] = useState(0);
    const [withdrawals, setWithdrawals] = useState([]);
    const [meta, setMeta] = useState({});
    const [currentPage, setCurrentPage] = useState(1);

    // Form state
    const [amount, setAmount] = useState("");
    const [phoneNumber, setPhoneNumber] = useState("");
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState({});

    // Fetch balance + withdrawal history on mount.
    useEffect(() => {
        let mounted = true;
        (async () => {
            try {
                await ensureSessionToken(auth.user?.id);

                const [earningsRes, withdrawalsRes] = await Promise.all([
                    xios.get("/api/earnings"),
                    xios.get("/api/withdrawals", {
                        params: { per_page: 10 },
                    }),
                ]);

                if (mounted) {
                    setBalance(
                        earningsRes.data.data?.current_balance ?? 0
                    );
                    setWithdrawals(withdrawalsRes.data.data);
                    setMeta(withdrawalsRes.data.meta);
                }
            } catch {
                if (mounted) toast.error("Unable to load withdrawal data.");
            } finally {
                if (mounted) setLoading(false);
            }
        })();
        return () => {
            mounted = false;
        };
    }, [auth.user?.id]);

    // Fetch withdrawals on page change.
    useEffect(() => {
        if (loading) return;
        let mounted = true;
        (async () => {
            try {
                const { data } = await xios.get("/api/withdrawals", {
                    params: { per_page: 10, page: currentPage },
                });
                if (mounted) {
                    setWithdrawals(data.data);
                    setMeta(data.meta);
                }
            } catch {
                // Silently fail on page change — data stays stale.
            }
        })();
        return () => {
            mounted = false;
        };
    }, [currentPage, loading]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        try {
            await ensureSessionToken(auth.user?.id);
            await xios.post("/api/withdrawals", {
                amount: Number(amount),
                phone_number: phoneNumber,
            });

            toast.success("Withdrawal request submitted!");
            setAmount("");
            setPhoneNumber("");

            // Refresh balance + history.
            const [earningsRes, withdrawalsRes] = await Promise.all([
                xios.get("/api/earnings"),
                xios.get("/api/withdrawals", {
                    params: { per_page: 10, page: 1 },
                }),
            ]);
            setBalance(earningsRes.data.data?.current_balance ?? 0);
            setWithdrawals(withdrawalsRes.data.data);
            setMeta(withdrawalsRes.data.meta);
            setCurrentPage(1);
        } catch (err) {
            if (err?.response?.status === 422) {
                setErrors(err.response.data.errors || {});
            }
            toast.error(
                err?.response?.data?.message ||
                    "Failed to submit withdrawal request."
            );
        } finally {
            setProcessing(false);
        }
    };

    const statusMeta = {
        pending: { label: "Pending", color: "warning" },
        processing: { label: "Processing", color: "info" },
        completed: { label: "Completed", color: "success" },
        failed: { label: "Failed", color: "danger" },
        cancelled: { label: "Cancelled", color: "secondary" },
    };

    if (loading) {
        return (
            <AppLayout>
                <Container className="py-5 text-center">
                    <Spinner animation="border" variant="gold" />
                </Container>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Withdrawals" />

            <Container className="py-4">
                {/* Header */}
                <div className="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h1 className="mb-0">Withdrawals</h1>
                        <p className="text-white-50 mb-0">
                            Request payouts and view withdrawal history
                        </p>
                    </div>
                    <Link
                        href={route("earnings.index")}
                        className="btn btn-outline-secondary rounded-3"
                    >
                        <ArrowLeft size={16} className="me-1" />
                        Earnings
                    </Link>
                </div>

                {/* Balance card */}
                <Card className="shadow-sm border-0 mb-4">
                    <Card.Body className="d-flex align-items-center gap-3">
                        <div
                            className="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                            style={{ width: 56, height: 56 }}
                        >
                            <Wallet size={28} className="text-success" />
                        </div>
                        <div>
                            <h6 className="text-success fw-semibold mb-1">
                                Available Balance
                            </h6>
                            <h3 className="fw-bold mb-0">
                                {Number(balance).toLocaleString()}{" "}
                                <small className="text-white-50 fs-6">
                                    credits
                                </small>
                            </h3>
                        </div>
                    </Card.Body>
                </Card>

                <Row className="g-4">
                    {/* Withdrawal form */}
                    <Col lg={5}>
                        <Card className="shadow-sm border-0 h-100">
                            <Card.Body className="p-4">
                                <h5 className="fw-bold mb-1">
                                    Request Withdrawal
                                </h5>
                                <p className="text-white-50 mb-4">
                                    Convert your credits to M-Pesa cash
                                </p>

                                <Form onSubmit={handleSubmit}>
                                    <Form.Group className="mb-3">
                                        <Form.Label className="fw-semibold">
                                            Amount (credits)
                                        </Form.Label>
                                        <Form.Control
                                            type="number"
                                            min="1"
                                            step="0.01"
                                            value={amount}
                                            onChange={(e) =>
                                                setAmount(e.target.value)
                                            }
                                            isInvalid={!!errors.amount}
                                            placeholder="e.g. 500"
                                            required
                                            className="bg-dark text-white border-secondary"
                                        />
                                        <Form.Control.Feedback type="invalid">
                                            {errors.amount?.[0]}
                                        </Form.Control.Feedback>
                                    </Form.Group>

                                    <Form.Group className="mb-4">
                                        <Form.Label className="fw-semibold">
                                            M-Pesa Phone Number
                                        </Form.Label>
                                        <Form.Control
                                            type="tel"
                                            value={phoneNumber}
                                            onChange={(e) =>
                                                setPhoneNumber(e.target.value)
                                            }
                                            isInvalid={
                                                !!errors.phone_number
                                            }
                                            placeholder="2547XXXXXXXX"
                                            pattern="2547\d{8}"
                                            required
                                            className="bg-dark text-white border-secondary"
                                        />
                                        <Form.Control.Feedback type="invalid">
                                            {errors.phone_number?.[0]}
                                        </Form.Control.Feedback>
                                        <Form.Text className="text-white-50">
                                            Format: 2547XXXXXXXX (12 digits)
                                        </Form.Text>
                                    </Form.Group>

                                    <Button
                                        type="submit"
                                        variant="gold"
                                        className="w-100 py-2 fw-bold"
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <>
                                                <Spinner
                                                    animation="border"
                                                    size="sm"
                                                    className="me-2"
                                                />
                                                Submitting...
                                            </>
                                        ) : (
                                            <>
                                                <Send
                                                    size={16}
                                                    className="me-2"
                                                />
                                                Request Withdrawal
                                            </>
                                        )}
                                    </Button>
                                </Form>
                            </Card.Body>
                        </Card>
                    </Col>

                    {/* Withdrawal history */}
                    <Col lg={7}>
                        <Card className="shadow-sm border-0 h-100">
                            <Card.Body className="p-0">
                                <div className="p-3 border-bottom border-secondary">
                                    <h5 className="mb-0 fw-semibold">
                                        Withdrawal History
                                    </h5>
                                </div>

                                {withdrawals.length === 0 ? (
                                    <div className="text-center py-5">
                                        <p className="text-white-50 mb-0">
                                            No withdrawals yet.
                                        </p>
                                    </div>
                                ) : (
                                    <>
                                        <Table
                                            responsive
                                            hover
                                            className="mb-0 align-middle"
                                            variant="dark"
                                        >
                                            <thead>
                                                <tr>
                                                    <th>Amount</th>
                                                    <th>KES</th>
                                                    <th>Phone</th>
                                                    <th>Status</th>
                                                    <th className="text-end">
                                                        Date
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {withdrawals.map((w) => {
                                                    const s =
                                                        statusMeta[w.status] ||
                                                        statusMeta.pending;
                                                    return (
                                                        <tr key={w.id}>
                                                            <td className="fw-semibold">
                                                                {Number(
                                                                    w.amount
                                                                ).toLocaleString()}{" "}
                                                                <small className="text-white-50">
                                                                    credits
                                                                </small>
                                                            </td>
                                                            <td>
                                                                {Number(
                                                                    w.amount_kes
                                                                ).toLocaleString()}{" "}
                                                                <small className="text-white-50">
                                                                    KES
                                                                </small>
                                                            </td>
                                                            <td className="text-white-50 font-monospace">
                                                                {w.phone_number ||
                                                                    "—"}
                                                            </td>
                                                            <td>
                                                                <Badge
                                                                    bg={s.color}
                                                                >
                                                                    {s.label}
                                                                </Badge>
                                                                {w.failure_reason && (
                                                                    <br />
                                                                )}
                                                                {w.failure_reason && (
                                                                    <small className="text-danger">
                                                                        {
                                                                            w.failure_reason
                                                                        }
                                                                    </small>
                                                                )}
                                                            </td>
                                                            <td className="text-end text-white-50">
                                                                {dayjs(
                                                                    w.created_at
                                                                ).format(
                                                                    "MMM D, YYYY h:mm A"
                                                                )}
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </Table>

                                        {/* Pagination */}
                                        {meta.last_page > 1 && (
                                            <div className="d-flex justify-content-between p-3 border-top border-secondary">
                                                <small className="text-white-50">
                                                    Page {meta.current_page} of{" "}
                                                    {meta.last_page}
                                                </small>
                                                <Pagination
                                                    size="sm"
                                                    className="mb-0"
                                                >
                                                    {Array.from(
                                                        {
                                                            length: meta.last_page,
                                                        },
                                                        (_, i) => i + 1
                                                    ).map((page) => (
                                                        <Pagination.Item
                                                            key={page}
                                                            active={
                                                                page ===
                                                                meta.current_page
                                                            }
                                                            onClick={() =>
                                                                setCurrentPage(
                                                                    page
                                                                )
                                                            }
                                                            linkClassName="bg-dark border-secondary text-white"
                                                        >
                                                            {page}
                                                        </Pagination.Item>
                                                    ))}
                                                </Pagination>
                                            </div>
                                        )}
                                    </>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Container>
        </AppLayout>
    );
};

export default Withdrawals;

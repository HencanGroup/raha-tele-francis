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
} from "react-bootstrap";
import { ArrowLeft, TrendingUp, Wallet, Banknote } from "lucide-react";
import { ensureSessionToken } from "@/Utils/auth";
import { toast } from "react-toastify";
import xios from "@/Utils/xios";
import dayjs from "dayjs";

/**
 * Escort earnings dashboard — displays balance, total earnings, and a
 * paginated transaction history with type filtering. Data is fetched
 * client-side from the Sanctum API.
 */
const Earnings = () => {
    const { auth } = usePage().props;

    const [loading, setLoading] = useState(true);
    const [earnings, setEarnings] = useState(null);
    const [transactions, setTransactions] = useState([]);
    const [meta, setMeta] = useState({});
    const [typeFilter, setTypeFilter] = useState("");
    const [currentPage, setCurrentPage] = useState(1);
    const [txLoading, setTxLoading] = useState(false);

    // Fetch earnings summary + transaction history on mount.
    useEffect(() => {
        let mounted = true;
        (async () => {
            try {
                await ensureSessionToken(auth.user?.id);

                const [earningsRes, txRes] = await Promise.all([
                    xios.get("/api/earnings"),
                    xios.get("/api/earnings/transactions", {
                        params: { per_page: 10 },
                    }),
                ]);

                if (mounted) {
                    setEarnings(earningsRes.data.data);
                    setTransactions(txRes.data.data);
                    setMeta(txRes.data.meta);
                }
            } catch {
                if (mounted) toast.error("Unable to load earnings data.");
            } finally {
                if (mounted) setLoading(false);
            }
        })();
        return () => {
            mounted = false;
        };
    }, [auth.user?.id]);

    // Fetch transactions when page or type filter changes.
    useEffect(() => {
        if (loading) return;
        let mounted = true;
        (async () => {
            setTxLoading(true);
            try {
                const params = { per_page: 10, page: currentPage };
                if (typeFilter) params.type = typeFilter;

                const { data } = await xios.get("/api/earnings/transactions", {
                    params,
                });
                if (mounted) {
                    setTransactions(data.data);
                    setMeta(data.meta);
                }
            } catch {
                if (mounted) toast.error("Unable to load transactions.");
            } finally {
                if (mounted) setTxLoading(false);
            }
        })();
        return () => {
            mounted = false;
        };
    }, [currentPage, typeFilter, loading]);

    const handleTypeFilter = (value) => {
        setTypeFilter(value);
        setCurrentPage(1);
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

    const txTypeMeta = {
        commission: { label: "Commission", color: "success" },
        usage: { label: "Usage", color: "info" },
        withdrawal: { label: "Withdrawal", color: "warning" },
        bonus: { label: "Bonus", color: "primary" },
        purchase: { label: "Purchase", color: "secondary" },
        refund: { label: "Refund", color: "danger" },
        platform_commission: { label: "Platform", color: "secondary" },
    };

    return (
        <AppLayout>
            <Head title="Earnings" />

            <Container className="py-4">
                {/* Header */}
                <div className="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h1 className="mb-0">Earnings</h1>
                        <p className="text-white-50 mb-0">
                            Track your balance and transaction history
                        </p>
                    </div>
                    <Link
                        href={route("dashboard")}
                        className="btn btn-outline-secondary rounded-3"
                    >
                        <ArrowLeft size={16} className="me-1" />
                        Dashboard
                    </Link>
                </div>

                {/* Stats cards */}
                <Row className="g-3 mb-4">
                    <Col md={4}>
                        <Card className="shadow-sm border-0 h-100">
                            <Card.Body className="d-flex align-items-center gap-3">
                                <div
                                    className="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                                    style={{ width: 56, height: 56 }}
                                >
                                    <Wallet size={28} className="text-success" />
                                </div>
                                <div>
                                    <h6 className="text-success fw-semibold mb-1">
                                        Current Balance
                                    </h6>
                                    <h3 className="fw-bold mb-0">
                                        {Number(
                                            earnings?.current_balance ?? 0
                                        ).toLocaleString()}{" "}
                                        <small className="text-white-50 fs-6">
                                            credits
                                        </small>
                                    </h3>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={4}>
                        <Card className="shadow-sm border-0 h-100">
                            <Card.Body className="d-flex align-items-center gap-3">
                                <div
                                    className="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                    style={{ width: 56, height: 56 }}
                                >
                                    <TrendingUp
                                        size={28}
                                        className="text-primary"
                                    />
                                </div>
                                <div>
                                    <h6 className="text-primary fw-semibold mb-1">
                                        Total Earnings
                                    </h6>
                                    <h3 className="fw-bold mb-0">
                                        {Number(
                                            earnings?.total_earnings ?? 0
                                        ).toLocaleString()}{" "}
                                        <small className="text-white-50 fs-6">
                                            credits
                                        </small>
                                    </h3>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={4}>
                        <Card className="shadow-sm border-0 h-100">
                            <Card.Body className="d-flex align-items-center gap-3">
                                <div
                                    className="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center"
                                    style={{ width: 56, height: 56 }}
                                >
                                    <Banknote
                                        size={28}
                                        className="text-warning"
                                    />
                                </div>
                                <div>
                                    <h6 className="text-warning fw-semibold mb-1">
                                        Available to Withdraw
                                    </h6>
                                    <h3 className="fw-bold mb-0">
                                        {Number(
                                            earnings?.current_balance ?? 0
                                        ).toLocaleString()}{" "}
                                        <small className="text-white-50 fs-6">
                                            credits
                                        </small>
                                    </h3>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>

                {/* Transaction history */}
                <Card className="border-0 shadow-sm">
                    <Card.Body className="p-0">
                        <div className="d-flex align-items-center justify-content-between p-3 border-bottom border-secondary">
                            <h5 className="mb-0 fw-semibold">
                                Transaction History
                            </h5>
                            <Form.Select
                                value={typeFilter}
                                onChange={(e) =>
                                    handleTypeFilter(e.target.value)
                                }
                                style={{ width: "auto" }}
                                className="bg-dark text-white border-secondary"
                            >
                                <option value="">All Types</option>
                                <option value="commission">Commission</option>
                                <option value="usage">Usage</option>
                                <option value="withdrawal">Withdrawal</option>
                                <option value="bonus">Bonus</option>
                            </Form.Select>
                        </div>

                        {txLoading ? (
                            <div className="text-center py-5">
                                <Spinner
                                    animation="border"
                                    variant="gold"
                                    size="sm"
                                />
                            </div>
                        ) : transactions.length === 0 ? (
                            <div className="text-center py-5">
                                <p className="text-white-50 mb-0">
                                    No transactions found.
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
                                            <th>Type</th>
                                            <th className="text-end">
                                                Amount
                                            </th>
                                            <th className="text-end">
                                                Balance
                                            </th>
                                            <th>Description</th>
                                            <th className="text-end">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {transactions.map((tx) => {
                                            const meta_ =
                                                txTypeMeta[tx.type] || {
                                                    label: tx.type,
                                                    color: "secondary",
                                                };
                                            const isCredit =
                                                tx.type === "commission" ||
                                                tx.type === "bonus" ||
                                                tx.type === "refund";
                                            return (
                                                <tr key={tx.id}>
                                                    <td>
                                                        <Badge
                                                            bg={meta_.color}
                                                        >
                                                            {meta_.label}
                                                        </Badge>
                                                    </td>
                                                    <td
                                                        className={`text-end fw-semibold ${
                                                            isCredit
                                                                ? "text-success"
                                                                : "text-danger"
                                                        }`}
                                                    >
                                                        {isCredit ? "+" : ""}
                                                        {Number(
                                                            tx.amount
                                                        ).toLocaleString()}
                                                    </td>
                                                    <td className="text-end text-white-50">
                                                        {Number(
                                                            tx.balance_before
                                                        ).toLocaleString()}{" "}
                                                        →{" "}
                                                        {Number(
                                                            tx.balance_after
                                                        ).toLocaleString()}
                                                    </td>
                                                    <td className="text-white-50">
                                                        {tx.description ||
                                                            "—"}
                                                    </td>
                                                    <td className="text-end text-white-50">
                                                        {dayjs(
                                                            tx.created_at
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
                                            {meta.last_page} (
                                            {meta.total} transactions)
                                        </small>
                                        <Pagination
                                            size="sm"
                                            className="mb-0"
                                        >
                                            {Array.from(
                                                { length: meta.last_page },
                                                (_, i) => i + 1
                                            ).map((page) => (
                                                <Pagination.Item
                                                    key={page}
                                                    active={
                                                        page ===
                                                        meta.current_page
                                                    }
                                                    onClick={() =>
                                                        setCurrentPage(page)
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
            </Container>
        </AppLayout>
    );
};

export default Earnings;

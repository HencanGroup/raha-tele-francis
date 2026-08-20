import AppLayout from "@/Layouts/AppLayout";
import { Head, Link } from "@inertiajs/react";
import { Container, Card, Table, Badge, Pagination } from "react-bootstrap";
import { ArrowLeft } from "lucide-react";
import dayjs from "dayjs";

/**
 * Credit ledger listing — members see their spending history, escorts see
 * their earnings history. Reached from the dashboard transactions widget.
 * Paginated 5 rows per page; page links are Inertia visits (preserve state).
 */
const Transactions = ({ transactions, isMember }) => {
    // Inertia serialises a LengthAwarePaginator as { data, links, ... }.
    const rows = transactions?.data || [];
    const links = transactions?.links || [];
    const currentPage = transactions?.current_page || 1;

    const typeMeta = {
        purchase: { label: "Purchase", color: "success" },
        usage: { label: "Usage", color: "info" },
        commission: { label: "Commission", color: "warning" },
        welcome: { label: "Welcome", color: "primary" },
        bonus: { label: "Bonus", color: "secondary" },
        refund: { label: "Refund", color: "secondary" },
        withdrawal: { label: "Withdrawal", color: "danger" },
    };

    return (
        <AppLayout>
            <Head title="Transactions" />

            <Container className="py-4">
                <div className="d-flex align-items-center justify-content-between mb-4">
                    <h1 className="mb-0">🧾 Transactions</h1>
                    <Link
                        href="/dashboard"
                        className="btn btn-outline-secondary rounded d-inline-flex align-items-center gap-2"
                    >
                        <ArrowLeft size={16} />
                        Dashboard
                    </Link>
                </div>

                {rows.length === 0 ? (
                    <Card className="border-0 shadow-sm text-center py-5">
                        <Card.Body>
                            <i className="bi bi-receipt fs-1 text-white-50 d-block mb-3"></i>
                            <h5 className="text-white-50">No transactions yet</h5>
                        </Card.Body>
                    </Card>
                ) : (
                    <Card className="border-0 shadow-sm overflow-hidden">
                        <Table
                            responsive
                            hover
                            className="mb-0 align-middle"
                            variant="dark"
                        >
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th className="text-end">Amount</th>
                                    <th className="text-end">Balance</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((tx) => {
                                    const meta =
                                        typeMeta[tx.type] || {
                                            label: tx.type,
                                            color: "secondary",
                                        };
                                    const isCredit = Number(tx.amount) >= 0;

                                    return (
                                        <tr key={tx.id}>
                                            <td>
                                                <Badge
                                                    bg={meta.color}
                                                    className="text-capitalize"
                                                >
                                                    {meta.label}
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
                                                {tx.amount}
                                            </td>
                                            <td className="text-end text-white-50">
                                                {tx.balance_before} →{" "}
                                                {tx.balance_after}
                                            </td>
                                            <td className="text-white-50">
                                                {dayjs(tx.created_at).format(
                                                    "MMM D, YYYY h:mm A",
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </Table>

                        <div className="d-flex align-items-center justify-content-between p-3 border-top border-secondary">
                            <small className="text-white-50">
                                Page {currentPage} of {transactions.last_page}
                            </small>
                            <Pagination size="sm" className="mb-0">
                                {links.map((link, index) => {
                                    // Laravel emits "..." markers with a null
                                    // url — render them as disabled ellipsis.
                                    if (!link.url) {
                                        return (
                                            <Pagination.Ellipsis
                                                key={index}
                                                disabled
                                            />
                                        );
                                    }

                                    return (
                                        <Pagination.Item
                                            key={index}
                                            active={link.active}
                                            as={Link}
                                            href={link.url}
                                            preserveState
                                            preserveScroll
                                        >
                                            {link.label
                                                .replaceAll("&laquo;", "‹")
                                                .replaceAll("&raquo;", "›")}
                                        </Pagination.Item>
                                    );
                                })}
                            </Pagination>
                        </div>
                    </Card>
                )}
            </Container>
        </AppLayout>
    );
};

export default Transactions;
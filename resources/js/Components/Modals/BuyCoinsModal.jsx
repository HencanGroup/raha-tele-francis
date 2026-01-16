import { usePage } from "@inertiajs/react";
import { Modal, Button, Form, Row, Col, Alert, Spinner } from "react-bootstrap";
import { useState, useEffect, useMemo } from "react";
import { Coins, Wallet } from "lucide-react";
import { calculateCoins, formatPhoneNumber } from "@/Utils/helpers";
import { useErrorToast } from "@/Hooks/useErrorToast";
import { toast } from "react-toastify";
import xios from "@/Utils/xios";

const PRESET_AMOUNTS = [100, 250, 500, 1000, 2500];

const BuyCoinsModal = ({
    showBuyCoinsModal,
    setShowBuyCoinsModal,
    onCoinsPurchased,
}) => {
    const { auth } = usePage().props;

    const [selectedIndex, setSelectedIndex] = useState(0);
    const [customAmount, setCustomAmount] = useState("");
    const [phoneNumber, setPhoneNumber] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");
    const { showErrorToast } = useErrorToast();

    const isCustom = selectedIndex === PRESET_AMOUNTS.length;

    const amount = isCustom
        ? Number(customAmount)
        : PRESET_AMOUNTS[selectedIndex];

    const coinsToAward = useMemo(() => calculateCoins(amount), [amount]);

    useEffect(() => {
        if (auth.user?.phone) {
            setPhoneNumber(auth?.user?.phone);
        }
    }, [auth.user]);

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!phoneNumber) {
            setError("Enter M-Pesa phone number");
            return;
        }

        if (!amount || amount < 1) {
            setError("Enter a valid amount");
            return;
        }

        setLoading(true);
        setError("");
        setSuccess("");

        try {
            const response = await xios.post(route("mpesa.stk-push"), {
                amount,
                phone: formatPhoneNumber(phoneNumber),
                credits_awarded: coinsToAward,
            });

            if (response.data.success) {
                toast.success(response.data.message);
                onCoinsPurchased?.(coinsToAward);
                setTimeout(() => {
                    setShowBuyCoinsModal(false);
                }, 2500);
            }
        } catch (error) {
            showErrorToast(error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            show={showBuyCoinsModal}
            onHide={() => !loading && setShowBuyCoinsModal(false)}
            centered
        >
            <Form onSubmit={handleSubmit}>
                <Modal.Header closeButton={!loading}>
                    <Modal.Title className="d-flex align-items-center">
                        <Wallet size={18} className="me-1" /> Recharge
                    </Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    {error && <Alert variant="danger">{error}</Alert>}
                    {success && <Alert variant="success">{success}</Alert>}

                    <h6 className="mb-3">
                        Balance: <Coins size={18} className="text-warning" />{" "}
                        {auth?.user?.credits}
                    </h6>

                    <Row className="g-2 mb-3">
                        {PRESET_AMOUNTS.map((amt, index) => (
                            <Col xs={6} md={4} key={amt}>
                                <Button
                                    className="w-100 h-100 fw-bold"
                                    variant={
                                        selectedIndex === index
                                            ? "gold"
                                            : "outline-gold"
                                    }
                                    onClick={() => setSelectedIndex(index)}
                                >
                                    KSh {amt}
                                    <br />
                                    <small>{calculateCoins(amt)} coins</small>
                                </Button>
                            </Col>
                        ))}

                        {/* Custom amount */}
                        <Col xs={6} md={4}>
                            <Button
                                className="w-100 h-100 fw-bold"
                                variant={isCustom ? "gold" : "outline-gold"}
                                onClick={() =>
                                    setSelectedIndex(PRESET_AMOUNTS.length)
                                }
                            >
                                Custom
                            </Button>
                        </Col>

                        {isCustom && (
                            <Col xs={12}>
                                <Form.Control
                                    type="number"
                                    min="1"
                                    placeholder="Enter amount (KSh)"
                                    value={customAmount}
                                    onChange={(e) =>
                                        setCustomAmount(e.target.value)
                                    }
                                    required
                                />
                                <Form.Text muted>
                                    You will receive{" "}
                                    <strong>{coinsToAward}</strong> coins
                                </Form.Text>
                            </Col>
                        )}
                    </Row>

                    <Form.Group className="mb-3">
                        <Form.Label>M-Pesa Phone Number</Form.Label>
                        <Form.Control
                            type="tel"
                            placeholder="0712345678"
                            value={phoneNumber}
                            onChange={(e) => setPhoneNumber(e.target.value)}
                            required
                        />
                    </Form.Group>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="submit"
                        variant="gold"
                        className="w-100"
                        size="lg"
                        disabled={loading}
                    >
                        {loading ? (
                            <>
                                <Spinner size="sm" /> Processing...
                            </>
                        ) : (
                            `Pay KSh ${amount || 0}`
                        )}
                    </Button>
                </Modal.Footer>
            </Form>
        </Modal>
    );
};

export default BuyCoinsModal;

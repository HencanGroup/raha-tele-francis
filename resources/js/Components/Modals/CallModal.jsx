import { hidePhoneNumber } from "@/Utils/helpers";
import { usePage, router } from "@inertiajs/react";
import { AlertCircle, Phone, Loader, Sparkles } from "lucide-react";
import { Alert, Button, Modal } from "react-bootstrap";
import { useState, useEffect } from "react";
import xios from "@/Utils/xios";
import { useErrorToast } from "@/Hooks/useErrorToast";
import BuyCoinsModal from "./BuyCoinsModal";

const CallModal = ({
    showCallModal,
    setShowCallModal,
    escort,
    initiallyUnlocked = false,
    onUnlocked,
}) => {
    const { auth, system_variables } = usePage().props;
    const { showErrorToast } = useErrorToast();
    const [showBuyCoinsModal, setShowBuyCoinsModal] = useState(false);

    /* ---------------- LOCAL STATE ---------------- */
    // Declared before COIN CONFIG — userCoins below reads walletBalance.
    const [isUnlocked, setIsUnlocked] = useState(initiallyUnlocked);
    const [loading, setLoading] = useState(false);
    // Fresh balance returned by the unlock API — overrides the shared prop
    // so the coin display reflects the deduction without a page reload.
    const [walletBalance, setWalletBalance] = useState(null);

    // Keep in sync when the server-backed unlock flag changes (e.g. the
    // parent re-renders with a freshly loaded escort payload).
    useEffect(() => {
        if (initiallyUnlocked) setIsUnlocked(true);
    }, [initiallyUnlocked]);

    /* ---------------- AUTH STATE ---------------- */
    const isLoggedIn = !!auth?.user;

    /* ---------------- COIN CONFIG ---------------- */
    const UNLOCK_COST = parseInt(system_variables?.phone_unlock_cost || 10);
    // Number() + ?? 0 — a missing/undefined balance must never produce
    // "undefined coins" or NaN in the shortfall math below. The local
    // walletBalance (set after a successful unlock) takes precedence.
    const userCoins = isLoggedIn
        ? Number((walletBalance ?? auth.user.credits) ?? 0)
        : 0;
    const hasSufficientCoins = isLoggedIn && userCoins >= UNLOCK_COST;
    const coinsShortfall = Math.max(UNLOCK_COST - userCoins, 0);

    /* ---------------- DATA ---------------- */
    const hiddenPhone = hidePhoneNumber(escort?.user?.phone_number);
    const realPhone = escort?.user?.phone_number;
    const escortName = escort?.user?.name || "this escort";

    /* ---------------- ALERT MESSAGES ---------------- */
    const ALERT_MESSAGES = {
        unauthenticated: {
            variant: "gold",
            icon: AlertCircle,
            title: "Login Required",
            message:
                "Please log in or create an account to unlock phone numbers and access premium features.",
        },
        insufficientCoins: {
            variant: "warning",
            icon: AlertCircle,
            title: "More Coins Needed",
            message: `You need ${UNLOCK_COST} coins to unlock this phone number. You're ${coinsShortfall} coin${
                coinsShortfall !== 1 ? "s" : ""
            } short.`,
        },
        costInfo: {
            variant: "info",
            icon: Sparkles,
            title: "Unlock Information",
            message: `Unlocking costs ${UNLOCK_COST} coins. You currently have ${userCoins} coin${
                userCoins !== 1 ? "s" : ""
            }.`,
        },
        unlockedSuccess: {
            variant: "success",
            icon: Phone,
            title: "Phone Number Unlocked!",
            message: `You can now call ${escortName} directly. NB: The number can only be viewed once, after that it will be hidden.`,
        },
        purchaseSuggestion: {
            variant: "light",
            icon: Sparkles,
            title: "Get More Coins",
            message: `Purchase more coins to unlock ${escortName}'s contact and other premium features.`,
        },
    };

    /* ---------------- ACTIONS ---------------- */
    const handleUnlockClick = async () => {
        if (!isLoggedIn) {
            router.visit(route("login"));
            return;
        }

        if (!hasSufficientCoins || loading || isUnlocked) return;

        setLoading(true);

        try {
            const response = await xios.post(
                route("api.escorts.unlock-phone", escort.id),
                {
                    escort_id: escort?.id,
                }
            );

            // The API wraps its payload in a `data` key
            // ({data: {success, credits}}) — fall back to the raw body so a
            // future shape change can't silently break the success path.
            const payload = response.data?.data ?? response.data;

            if (payload.success) {
                setIsUnlocked(true);

                if (payload.credits !== undefined && payload.credits !== null) {
                    setWalletBalance(payload.credits);
                }

                // Flip the page-level buttons to direct-dial immediately.
                onUnlocked?.();
            }
        } catch (error) {
            showErrorToast(error);
        } finally {
            setLoading(false);
        }
    };

    /* ---------------- RENDER ---------------- */
    return (
        <>
            <Modal
                show={showCallModal}
                onHide={() => setShowCallModal(false)}
                centered
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Contact {escortName}</Modal.Title>
                </Modal.Header>

                <Modal.Body className="pt-0">
                    {/* LOGIN REQUIRED */}
                    {!isLoggedIn && (
                        <Alert
                            variant={ALERT_MESSAGES.unauthenticated.variant}
                            className="d-flex align-items-start mb-4"
                        >
                            <ALERT_MESSAGES.unauthenticated.icon
                                className="me-2 mt-1"
                                size={18}
                            />
                            <div>
                                <Alert.Heading className="h6 mb-1">
                                    {ALERT_MESSAGES.unauthenticated.title}
                                </Alert.Heading>
                                <p className="small mb-2">
                                    {ALERT_MESSAGES.unauthenticated.message}
                                </p>
                                <Button
                                    variant="outline-warning"
                                    size="sm"
                                    onClick={() => router.visit(route("login"))}
                                >
                                    Login / Register
                                </Button>
                            </div>
                        </Alert>
                    )}

                    {/* INSUFFICIENT COINS */}
                    {isLoggedIn && !hasSufficientCoins && !isUnlocked && (
                        <Alert
                            variant={ALERT_MESSAGES.insufficientCoins.variant}
                            className="d-flex align-items-start mb-3"
                        >
                            <ALERT_MESSAGES.insufficientCoins.icon
                                className="me-2 mt-1"
                                size={18}
                            />
                            <div>
                                <Alert.Heading className="h6 mb-1">
                                    {ALERT_MESSAGES.insufficientCoins.title}
                                </Alert.Heading>
                                <p className="small mb-0">
                                    {ALERT_MESSAGES.insufficientCoins.message}
                                </p>
                            </div>
                        </Alert>
                    )}

                    {/* COST INFO */}
                    {isLoggedIn && !isUnlocked && (
                        <Alert
                            variant={ALERT_MESSAGES.costInfo.variant}
                            className="d-flex align-items-start mb-4"
                        >
                            <ALERT_MESSAGES.costInfo.icon
                                className="me-2 mt-1"
                                size={18}
                            />
                            <div>
                                <Alert.Heading className="h6 mb-1">
                                    {ALERT_MESSAGES.costInfo.title}
                                </Alert.Heading>
                                <p className="small mb-0">
                                    {ALERT_MESSAGES.costInfo.message}
                                </p>
                            </div>
                        </Alert>
                    )}

                    {/* PHONE DISPLAY */}
                    <div className="text-center mb-4 p-3 bg-light rounded">
                        <p className="text-muted small mb-1">PHONE NUMBER</p>
                        <div className="d-flex align-items-center justify-content-center">
                            <Phone className="me-2 text-muted" size={20} />
                            <p className="h4 fw-bold mb-0">
                                {isUnlocked ? (
                                    <a
                                        href={`tel:${realPhone}`}
                                        className="text-decoration-none text-dark"
                                    >
                                        {realPhone}
                                    </a>
                                ) : (
                                    <span className="text-muted">
                                        {hiddenPhone}
                                    </span>
                                )}
                            </p>
                        </div>
                        {!isUnlocked && (
                            <p className="text-muted small mt-2">
                                Unlock to reveal full number
                            </p>
                        )}
                    </div>

                    {/* ACTION BUTTONS */}
                    <div className="d-grid gap-2">
                        {!isUnlocked && (
                            <Button
                                variant={
                                    isLoggedIn && hasSufficientCoins
                                        ? "gold"
                                        : "secondary"
                                }
                                size="lg"
                                disabled={
                                    !isLoggedIn ||
                                    !hasSufficientCoins ||
                                    loading
                                }
                                onClick={handleUnlockClick}
                                className="fw-bold py-3"
                            >
                                {!isLoggedIn ? (
                                    "Login to Unlock"
                                ) : loading ? (
                                    <>
                                        <Loader className="me-2 spinner-border-sm" />
                                        Processing...
                                    </>
                                ) : (
                                    <>
                                        <Phone className="me-2" />
                                        Unlock for {UNLOCK_COST} Coins
                                    </>
                                )}
                            </Button>
                        )}

                        {isLoggedIn && !hasSufficientCoins && !isUnlocked && (
                            <>
                                <Button
                                    variant="warning"
                                    size="lg"
                                    onClick={() => setShowBuyCoinsModal(true)}
                                    className="fw-bold py-3"
                                >
                                    <Sparkles className="me-2" />
                                    Get More Coins
                                </Button>

                                <Alert
                                    variant={
                                        ALERT_MESSAGES.purchaseSuggestion
                                            .variant
                                    }
                                    className="mt-2"
                                >
                                    <ALERT_MESSAGES.purchaseSuggestion.icon
                                        className="me-2"
                                        size={16}
                                    />
                                    <span className="small">
                                        {
                                            ALERT_MESSAGES.purchaseSuggestion
                                                .message
                                        }
                                    </span>
                                </Alert>
                            </>
                        )}
                    </div>

                    {/* SUCCESS */}
                    {isUnlocked && (
                        <Alert
                            variant={ALERT_MESSAGES.unlockedSuccess.variant}
                            className="mt-4"
                        >
                            <div className="d-flex align-items-start">
                                <ALERT_MESSAGES.unlockedSuccess.icon
                                    className="me-2 mt-1"
                                    size={18}
                                />
                                <div>
                                    <Alert.Heading className="h6 mb-1">
                                        {ALERT_MESSAGES.unlockedSuccess.title}
                                    </Alert.Heading>
                                    <p className="small mb-0">
                                        {ALERT_MESSAGES.unlockedSuccess.message}
                                    </p>
                                    <Button
                                        variant="outline-success"
                                        size="sm"
                                        href={`tel:${realPhone}`}
                                        className="mt-2"
                                    >
                                        Call Now
                                    </Button>
                                </div>
                            </div>
                        </Alert>
                    )}
                </Modal.Body>
            </Modal>

            <BuyCoinsModal
                showBuyCoinsModal={showBuyCoinsModal}
                setShowBuyCoinsModal={setShowBuyCoinsModal}
            />
        </>
    );
};

export default CallModal;

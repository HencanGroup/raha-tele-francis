import { router } from "@inertiajs/react";
import { Button } from "react-bootstrap";

const StartChartBtn = ({
    user = null,
    className = "",
    displayText = "Start Chat",
}) => {
    const startConversation = () => {
        router.post(
            "/chat/start",
            { user_id: user.id },
            {
                onSuccess: () => {
                    //
                },
                onFinish: () => {
                    //
                },
            },
        );
    };

    return (
        <Button
            className={`${className} d-flex align-items-center`}
            onClick={startConversation}
        >
            {displayText || "Start Chat"}
        </Button>
    );
};

export default StartChartBtn;

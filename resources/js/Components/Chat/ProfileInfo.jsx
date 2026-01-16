import React, { memo } from "react";
import { Image, Button } from "react-bootstrap";
import { X, ShieldAlert, Ban } from "lucide-react";
import { getProfileImage } from "@/Utils/helpers";

const ProfileInfo = memo(({ otherUser, onClose }) => {
    if (!otherUser) {
        return (
            <div className="p-3 text-white-50">
                No profile information available
            </div>
        );
    }

    return (
        <div
            className="h-100 d-flex flex-column p-2 border-start border-dark"
            style={{ background: "#1a1a1a" }}
        >
            {/* HEADER */}
            <div className="text-end">
                <Button
                    variant="link"
                    className="text-white-50 p-0"
                    onClick={onClose}
                >
                    <X size={18} />
                </Button>
            </div>

            {/* CONTENT */}
            <div className="flex-grow-1 overflow-auto p-3">
                {/* AVATAR */}
                <div className="text-center mb-3">
                    <Image
                        src={getProfileImage(otherUser)}
                        roundedCircle
                        style={{
                            width: 96,
                            height: 96,
                            objectFit: "cover",
                        }}
                    />
                    <h5 className="mt-3 mb-1">
                        {otherUser.name || "Unknown User"}
                    </h5>
                </div>
            </div>

            {/* ACTIONS */}
            <div className="p-3">
                <Button
                    variant="outline-warning"
                    size="sm"
                    className="w-100 mb-2"
                >
                    <ShieldAlert size={14} className="me-2" />
                    Report User
                </Button>

                <Button variant="outline-danger" size="sm" className="w-100">
                    <Ban size={14} className="me-2" />
                    Block User
                </Button>
            </div>
        </div>
    );
});

export default ProfileInfo;

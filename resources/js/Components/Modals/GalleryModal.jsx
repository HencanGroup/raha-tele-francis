import { useState, useEffect } from "react";
import { Carousel, Image, Modal, Button } from "react-bootstrap";
import { Lock, Eye } from "lucide-react";

const GalleryModal = ({
    showGalleryModal,
    setShowGalleryModal,
    galleryImages = [],
    startIndex = 0,
    isLocked = () => false,
    onUnlockClick = () => {},
    mediaUnlockCost = 5,
}) => {
    const [currentImageIndex, setCurrentImageIndex] = useState(startIndex);

    // Sync carousel position when the modal opens at a specific index.
    useEffect(() => {
        if (showGalleryModal) {
            setCurrentImageIndex(startIndex);
        }
    }, [showGalleryModal, startIndex]);

    return (
        <Modal
            show={showGalleryModal}
            onHide={() => setShowGalleryModal(false)}
            size="xl"
            centered
        >
            <Modal.Header closeButton>
                <Modal.Title>Gallery</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Carousel
                    activeIndex={currentImageIndex}
                    onSelect={setCurrentImageIndex}
                >
                    {galleryImages.map((img) => (
                        <Carousel.Item key={img.id}>
                            <div className="text-center position-relative">
                                {img.type === "video" ? (
                                    isLocked(img) ? (
                                        <div
                                            className="d-flex flex-column align-items-center justify-content-center rounded"
                                            style={{
                                                height: "50vh",
                                                background: "rgba(0,0,0,0.6)",
                                            }}
                                        >
                                            <Lock size={48} className="text-white mb-3" />
                                            <h6 className="text-white">Private Video</h6>
                                            <p className="text-white-50 mb-3">
                                                Pay {mediaUnlockCost} credits to view
                                            </p>
                                            <Button
                                                variant="warning"
                                                onClick={() => {
                                                    setShowGalleryModal(false);
                                                    onUnlockClick(img);
                                                }}
                                            >
                                                <Eye size={16} className="me-1" />
                                                Unlock ({mediaUnlockCost} credits)
                                            </Button>
                                        </div>
                                    ) : (
                                        <video
                                            src={img.url}
                                            controls
                                            className="img-fluid rounded"
                                            style={{
                                                maxHeight: "70vh",
                                                maxWidth: "100%",
                                            }}
                                        />
                                    )
                                ) : isLocked(img) ? (
                                    <div
                                        className="d-flex flex-column align-items-center justify-content-center rounded"
                                        style={{
                                            height: "50vh",
                                            background: "rgba(0,0,0,0.6)",
                                        }}
                                    >
                                        <Lock size={48} className="text-white mb-3" />
                                        <h6 className="text-white">Private Photo</h6>
                                        <p className="text-white-50 mb-3">
                                            Pay {mediaUnlockCost} credits to view
                                        </p>
                                        <Button
                                            variant="warning"
                                            onClick={() => {
                                                setShowGalleryModal(false);
                                                onUnlockClick(img);
                                            }}
                                        >
                                            <Eye size={16} className="me-1" />
                                            Unlock ({mediaUnlockCost} credits)
                                        </Button>
                                    </div>
                                ) : (
                                    <Image
                                        src={img.url}
                                        className="img-fluid rounded"
                                        style={{
                                            maxHeight: "70vh",
                                            objectFit: "contain",
                                        }}
                                    />
                                )}
                            </div>
                        </Carousel.Item>
                    ))}
                </Carousel>
                <div className="text-center mt-3">
                    <p>
                        {galleryImages[currentImageIndex]?.caption ||
                            "No caption"}
                    </p>
                    <small className="text-white-50">
                        {currentImageIndex + 1} of {galleryImages.length}
                    </small>
                </div>
            </Modal.Body>
        </Modal>
    );
};

export default GalleryModal;

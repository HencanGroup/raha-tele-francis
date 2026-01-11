import { useState } from "react";
import { Carousel, Modal } from "react-bootstrap";

const GalleryModal = ({
    showCallModal,
    setShowCallModal,
    galleryImages = [],
}) => {
    const [currentImageIndex, setCurrentImageIndex] = useState(0);

    return (
        <Modal
            show={showCallModal}
            onHide={() => setShowCallModal(false)}
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
                    {galleryImages.map((img, index) => (
                        <Carousel.Item key={img.id}>
                            <div className="text-center">
                                <Image
                                    src={img.url}
                                    className="img-fluid rounded"
                                    style={{
                                        maxHeight: "70vh",
                                        objectFit: "contain",
                                    }}
                                />
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

import AppLayout from "@/Layouts/AppLayout";
import { Head, Link, usePage } from "@inertiajs/react";
import { useEffect, useState, useRef } from "react";
import {
    Container,
    Card,
    Row,
    Col,
    Button,
    Badge,
    Spinner,
    Form,
    Modal,
} from "react-bootstrap";
import {
    ArrowLeft,
    Upload,
    Trash2,
    Star,
    StarOff,
    Globe,
    Lock,
    Image as ImageIcon,
    Film,
} from "lucide-react";
import { ensureSessionToken } from "@/Utils/auth";
import { toast } from "react-toastify";
import xios from "@/Utils/xios";

/**
 * Escort media management page — upload, view, delete photos/videos,
 * set primary photo, toggle public/private visibility.
 */
const Media = () => {
    const { auth } = usePage().props;

    const [loading, setLoading] = useState(true);
    const [media, setMedia] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [caption, setCaption] = useState("");
    const [isPublic, setIsPublic] = useState(true);
    const [selectedFile, setSelectedFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const fileInputRef = useRef(null);

    // Fetch media on mount.
    useEffect(() => {
        let mounted = true;
        (async () => {
            try {
                await ensureSessionToken(auth.user?.id);
                const { data } = await xios.get("/api/media");
                if (mounted) setMedia(data.data);
            } catch {
                if (mounted) toast.error("Unable to load media.");
            } finally {
                if (mounted) setLoading(false);
            }
        })();
        return () => {
            mounted = false;
        };
    }, [auth.user?.id]);

    const handleFileSelect = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (file.size > 30 * 1024 * 1024) {
            toast.error("File must be no larger than 30 MB.");
            return;
        }

        setSelectedFile(file);
        const reader = new FileReader();
        reader.onload = (ev) => setPreview(ev.target.result);
        reader.readAsDataURL(file);
    };

    const handleUpload = async () => {
        if (!selectedFile) return;

        setUploading(true);
        try {
            await ensureSessionToken(auth.user?.id);

            const form = new FormData();
            form.append("file", selectedFile);
            if (caption.trim()) form.append("caption", caption.trim());
            form.append("is_public", isPublic ? "1" : "0");

            const { data } = await xios.post("/api/media", form, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            setMedia((prev) => [data.data, ...prev]);
            setSelectedFile(null);
            setPreview(null);
            setCaption("");
            setIsPublic(true);
            if (fileInputRef.current) fileInputRef.current.value = "";
            toast.success("Media uploaded successfully!");
        } catch (err) {
            toast.error(
                err?.response?.data?.message ||
                    err?.response?.data?.errors?.file?.[0] ||
                    "Upload failed."
            );
        } finally {
            setUploading(false);
        }
    };

    const handleDelete = async () => {
        if (!deleteTarget) return;

        setDeleting(true);
        try {
            await ensureSessionToken(auth.user?.id);
            await xios.delete(`/api/media/${deleteTarget.id}`);
            setMedia((prev) => prev.filter((m) => m.id !== deleteTarget.id));
            setShowDeleteModal(false);
            setDeleteTarget(null);
            toast.success("Media deleted.");
        } catch {
            toast.error("Failed to delete media.");
        } finally {
            setDeleting(false);
        }
    };

    const handleSetPrimary = async (id) => {
        try {
            await ensureSessionToken(auth.user?.id);
            await xios.post(`/api/media/${id}/primary`);
            setMedia((prev) =>
                prev.map((m) => ({ ...m, is_primary: m.id === id }))
            );
            toast.success("Primary photo updated.");
        } catch {
            toast.error("Failed to set primary photo.");
        }
    };

    const handleTogglePublic = async (id) => {
        try {
            await ensureSessionToken(auth.user?.id);
            const { data } = await xios.post(`/api/media/${id}/toggle-public`);
            setMedia((prev) =>
                prev.map((m) =>
                    m.id === id
                        ? { ...m, is_public: data.data.is_public }
                        : m
                )
            );
        } catch {
            toast.error("Failed to update visibility.");
        }
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
            <Head title="My Media" />

            <Container className="py-4">
                {/* Header */}
                <div className="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h1 className="mb-0">My Media</h1>
                        <p className="text-white-50 mb-0">
                            Manage your photos and videos
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

                {/* Upload section */}
                <Card className="shadow-sm border-0 mb-4">
                    <Card.Body className="p-4">
                        <h5 className="fw-bold mb-1">Upload New Media</h5>
                        <p className="text-white-50 mb-3" style={{ fontSize: "0.8rem" }}>
                            Accepted: JPG, PNG, GIF, WebP, MP4, MOV, MKV, WebM, AVI — max 30 MB
                        </p>

                        <Row className="g-3 align-items-end">
                            <Col md={4}>
                                <Form.Group>
                                    <Form.Label className="fw-semibold">
                                        Select File
                                    </Form.Label>
                                    <Form.Control
                                        type="file"
                                        accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/x-matroska,video/webm,video/avi,video/mpeg"
                                        onChange={handleFileSelect}
                                        ref={fileInputRef}
                                        className="bg-dark text-white border-secondary"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={3}>
                                <Form.Group>
                                    <Form.Label className="fw-semibold">
                                        Caption
                                    </Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={caption}
                                        onChange={(e) =>
                                            setCaption(e.target.value)
                                        }
                                        placeholder="Optional caption"
                                        maxLength={255}
                                        className="bg-dark text-white border-secondary"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={2}>
                                <Form.Group>
                                    <Form.Label className="fw-semibold invisible">
                                        Visibility
                                    </Form.Label>
                                    <Form.Check
                                        type="switch"
                                        id="is-public"
                                        label="Public"
                                        checked={isPublic}
                                        onChange={(e) =>
                                            setIsPublic(e.target.checked)
                                        }
                                        className="text-white"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={3}>
                                <Button
                                    variant="gold"
                                    className="w-100 py-2 fw-bold"
                                    onClick={handleUpload}
                                    disabled={!selectedFile || uploading}
                                >
                                    {uploading ? (
                                        <>
                                            <Spinner
                                                animation="border"
                                                size="sm"
                                                className="me-2"
                                            />
                                            Uploading...
                                        </>
                                    ) : (
                                        <>
                                            <Upload
                                                size={16}
                                                className="me-2"
                                            />
                                            Upload
                                        </>
                                    )}
                                </Button>
                            </Col>
                        </Row>

                        {/* Preview */}
                        {preview && (
                            <div className="mt-3">
                                <small className="text-white-50 d-block mb-2">
                                    Preview:
                                </small>
                                {selectedFile?.type?.startsWith("video/") ? (
                                    <video
                                        src={preview}
                                        controls
                                        className="rounded"
                                        style={{ maxHeight: 200 }}
                                    />
                                ) : (
                                    <img
                                        src={preview}
                                        alt="Preview"
                                        className="rounded"
                                        style={{ maxHeight: 200 }}
                                    />
                                )}
                            </div>
                        )}
                    </Card.Body>
                </Card>

                {/* Media grid */}
                {media.length === 0 ? (
                    <Card className="border-0 shadow-sm text-center py-5">
                        <Card.Body>
                            <ImageIcon
                                size={48}
                                className="text-white-50 mb-3"
                            />
                            <p className="text-white-50 mb-0">
                                No media uploaded yet. Use the form above to
                                add photos and videos.
                            </p>
                        </Card.Body>
                    </Card>
                ) : (
                    <Row className="g-3">
                        {media.map((item) => (
                            <Col key={item.id} xs={6} md={4} lg={3}>
                                <Card className="shadow-sm border-0 h-100">
                                    <div className="position-relative">
                                        {item.type === "video" ? (
                                            <div className="bg-dark d-flex align-items-center justify-content-center"
                                                style={{ height: 200 }}>
                                                <Film
                                                    size={48}
                                                    className="text-white-50"
                                                />
                                                <video
                                                    src={item.url}
                                                    className="w-100 h-100 position-absolute"
                                                    style={{
                                                        objectFit: "cover",
                                                    }}
                                                    muted
                                                />
                                            </div>
                                        ) : (
                                            <img
                                                src={item.url}
                                                alt={
                                                    item.caption || "Media"
                                                }
                                                className="w-100"
                                                style={{
                                                    height: 200,
                                                    objectFit: "cover",
                                                }}
                                            />
                                        )}

                                        {/* Badges overlay */}
                                        <div className="position-absolute top-0 start-0 p-2 d-flex gap-1">
                                            {item.is_primary && (
                                                <Badge bg="warning">
                                                    <Star size={12} /> Primary
                                                </Badge>
                                            )}
                                            {!item.is_public && (
                                                <Badge bg="secondary">
                                                    <Lock size={12} /> Private
                                                </Badge>
                                            )}
                                            {item.type === "video" && (
                                                <Badge bg="info">
                                                    <Film size={12} /> Video
                                                </Badge>
                                            )}
                                        </div>
                                    </div>

                                    <Card.Body className="p-2">
                                        <small className="text-white-50 d-block text-truncate">
                                            {item.caption || "No caption"}
                                        </small>
                                        <small className="text-white-50">
                                            {item.created_at_human}
                                        </small>

                                        <div className="d-flex gap-1 mt-2">
                                            {item.type === "photo" && (
                                                <Button
                                                    size="sm"
                                                    variant={
                                                        item.is_primary
                                                            ? "warning"
                                                            : "outline-secondary"
                                                    }
                                                    onClick={() =>
                                                        handleSetPrimary(
                                                            item.id
                                                        )
                                                    }
                                                    title="Set as primary photo"
                                                >
                                                    {item.is_primary ? (
                                                        <StarOff size={14} />
                                                    ) : (
                                                        <Star size={14} />
                                                    )}
                                                </Button>
                                            )}
                                            <Button
                                                size="sm"
                                                variant={
                                                    item.is_public
                                                        ? "outline-success"
                                                        : "outline-secondary"
                                                }
                                                onClick={() =>
                                                    handleTogglePublic(
                                                        item.id
                                                    )
                                                }
                                                title={
                                                    item.is_public
                                                        ? "Make private"
                                                        : "Make public"
                                                }
                                            >
                                                {item.is_public ? (
                                                    <Globe size={14} />
                                                ) : (
                                                    <Lock size={14} />
                                                )}
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline-danger"
                                                onClick={() => {
                                                    setDeleteTarget(item);
                                                    setShowDeleteModal(true);
                                                }}
                                                title="Delete"
                                            >
                                                <Trash2 size={14} />
                                            </Button>
                                        </div>
                                    </Card.Body>
                                </Card>
                            </Col>
                        ))}
                    </Row>
                )}
            </Container>

            {/* Delete confirmation modal */}
            <Modal
                show={showDeleteModal}
                onHide={() => {
                    setShowDeleteModal(false);
                    setDeleteTarget(null);
                }}
                centered
            >
                <Modal.Header
                    closeButton
                    className="bg-dark text-white border-secondary"
                >
                    <Modal.Title>Delete Media</Modal.Title>
                </Modal.Header>
                <Modal.Body className="bg-dark text-white">
                    Are you sure you want to delete this{" "}
                    {deleteTarget?.type === "video" ? "video" : "photo"}?
                    This action cannot be undone.
                </Modal.Body>
                <Modal.Footer className="bg-dark border-secondary">
                    <Button
                        variant="secondary"
                        onClick={() => {
                            setShowDeleteModal(false);
                            setDeleteTarget(null);
                        }}
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="danger"
                        onClick={handleDelete}
                        disabled={deleting}
                    >
                        {deleting ? (
                            <>
                                <Spinner
                                    animation="border"
                                    size="sm"
                                    className="me-2"
                                />
                                Deleting...
                            </>
                        ) : (
                            "Delete"
                        )}
                    </Button>
                </Modal.Footer>
            </Modal>
        </AppLayout>
    );
};

export default Media;

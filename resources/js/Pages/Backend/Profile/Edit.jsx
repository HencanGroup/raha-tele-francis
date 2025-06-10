import { Head, usePage } from "@inertiajs/react";
import { useState, useCallback, useMemo } from "react";
import {
    Container, Card, Form, Button, Row, Col, Spinner, Image, ProgressBar
} from "react-bootstrap";
import AppLayout from "@/Layouts/AppLayout";
import axios from "axios";
import Swal from "sweetalert2";
import { toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import { useDropzone } from "react-dropzone";

export default function ProfileUpdate({ auth }) {
    const { flash } = usePage().props;

    const initial = {
        name: auth.user.name || "",
        email: auth.user.email || "",
        gender: auth.user.gender || "",
        searching_for: auth.user.searching_for || "",
        birth_date: auth.user.birth_date || "",
        bio: auth.user.bio || "",
        profile_picture: auth.user.profile_picture || null,
        gallery: auth.user.gallery || [],
        new_gallery_files: [],
        location: auth.user.location || "",
        phone_number: auth.user.phone_number || "",
        verification_documents: [],
    };

    const [data, setData] = useState(initial);
    const [processing, setProcessing] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);

    const handleInput = e => {
        const { name, value } = e.target;
        setData(prev => ({ ...prev, [name]: value }));
    };

    const onProfilePicDrop = useCallback(acceptedFiles => {
        if (acceptedFiles.length) {
            handleFile("profile_picture", acceptedFiles[0]);
        }
    }, []);

    const onGalleryDrop = useCallback(acceptedFiles => {
        if (acceptedFiles.length + data.gallery.length + data.new_gallery_files.length > 6) {
            toast.error("You can upload a maximum of 6 gallery images");
            return;
        }
        setData(prev => ({ ...prev, new_gallery_files: [...prev.new_gallery_files, ...acceptedFiles] }));
    }, [data.gallery.length, data.new_gallery_files.length]);

    const onDocsDrop = useCallback(acceptedFiles => {
        setData(prev => ({ ...prev, verification_documents: [...prev.verification_documents, ...acceptedFiles] }));
    }, []);

    const {
        getRootProps: getProfilePicRootProps,
        getInputProps: getProfilePicInputProps,
        isDragActive: isProfilePicDragActive
    } = useDropzone({
        onDrop: onProfilePicDrop,
        accept: { 'image/*': [] },
        maxFiles: 1
    });

    const {
        getRootProps: getGalleryRootProps,
        getInputProps: getGalleryInputProps,
        isDragActive: isGalleryDragActive
    } = useDropzone({
        onDrop: onGalleryDrop,
        accept: { 'image/*': [] },
        maxFiles: 6 - (data.gallery.length + data.new_gallery_files.length)
    });

    const {
        getRootProps: getDocsRootProps,
        getInputProps: getDocsInputProps,
        isDragActive: isDocsDragActive
    } = useDropzone({
        onDrop: onDocsDrop,
        multiple: true
    });

    const handleFile = (key, file) => {
        setData(prev => ({ ...prev, [key]: file }));
    };

    const removeGalleryImage = (index, isNew = false) => {
        Swal.fire({
            title: "Remove image?",
            text: "This cannot be undone.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Remove"
        }).then(res => {
            if (res.isConfirmed) {
                if (isNew) {
                    const g = [...data.new_gallery_files];
                    g.splice(index, 1);
                    setData(prev => ({ ...prev, new_gallery_files: g }));
                } else {
                    const g = [...data.gallery];
                    g.splice(index, 1);
                    setData(prev => ({ ...prev, gallery: g }));
                }
                toast.success("Image removed");
            }
        });
    };

    const removeDocument = (index) => {
        Swal.fire({
            title: "Remove document?",
            text: "This cannot be undone.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Remove"
        }).then(res => {
            if (res.isConfirmed) {
                const docs = [...data.verification_documents];
                docs.splice(index, 1);
                setData(prev => ({ ...prev, verification_documents: docs }));
                toast.success("Document removed");
            }
        });
    };

    const handleSubmit = async e => {
        e.preventDefault();
        const confirmed = await Swal.fire({
            title: "Confirm Update",
            text: "Are you sure you want to update your profile?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, update it!",
            cancelButtonText: "No, cancel"
        });

        if (!confirmed.isConfirmed) return;

        setProcessing(true);
        setUploadProgress(0);

        Swal.fire({
            title: "Updating...",
            html: `
                <div class="text-center">
                    <p>Please wait while we update your profile.</p>
                    <div class="mt-3">
                        <ProgressBar now={uploadProgress} style={{ height: '10px' }} />
                    </div>
                </div>
            `,
            icon: "info",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const fd = new FormData();
            ["name", "email", "gender", "searching_for", "birth_date", "bio", "location", "phone_number"].forEach(key =>
                fd.append(key, data[key] ?? "")
            );

            if (data.profile_picture instanceof File) {
                fd.append("profile_picture", data.profile_picture);
            }

            data.gallery.forEach((img, i) => fd.append(`gallery_existing[${i}]`, img));
            data.new_gallery_files.forEach((file, i) => fd.append(`gallery_new[${i}]`, file));
            data.verification_documents.forEach((file, i) =>
                fd.append(`verification_documents[${i}]`, file)
            );

            fd.append('_method', 'PATCH');

            const response = await axios.post(route("profile.update", auth.user.id), fd, {
                headers: { "Content-Type": "multipart/form-data" },
                onUploadProgress: progressEvent => {
                    const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    setUploadProgress(progress);
                    // Update Swal progress
                    const progressBar = document.querySelector('.swal2-progress-bar');
                    if (progressBar) {
                        progressBar.style.width = `${progress}%`;
                    }
                }
            });

            Swal.close();

            if (response.data.success) {
                Swal.fire({
                    title: "Profile Updated",
                    text: "Your profile has been successfully updated.",
                    icon: "success",
                    confirmButtonText: "OK"
                });
                setData(initial);
            } else {
                toast.error(response.data.message || "Failed to update profile");
            }
        } catch (error) {
            Swal.close();
            if (error.response?.status === 422) {
                const errs = Object.values(error.response.data.errors).flat();
                errs.forEach(m => toast.error(m));
            } else {
                toast.error(error.response?.data?.message || "Failed to update profile");
            }
        } finally {
            setProcessing(false);
        }
    };

    const formatDate = (dateString) => {
        return dateString ? new Date(dateString).toISOString().split('T')[0] : '';
    };

    const profilePicPreview = useMemo(() => {
        if (data.profile_picture instanceof File) {
            return URL.createObjectURL(data.profile_picture);
        } else if (data.profile_picture) {
            return `/storage/${data.profile_picture}`;
        }
        return null;
    }, [data.profile_picture]);

    const galleryPreviews = useMemo(() => {
        return data.new_gallery_files.map(file => URL.createObjectURL(file));
    }, [data.new_gallery_files]);

    return (
        <AppLayout user={auth.user}>
            <Head title="Profile Update" />
            <Container className="py-5">
                <h2 className="fw-bold mb-4 text-center">Update Your Profile</h2>
                <Card className="shadow-sm border-0 rounded-lg overflow-hidden">
                    <Card.Body className="p-4">
                        <Form onSubmit={handleSubmit}>
                            <Row className="g-4">
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label className="fw-semibold">Full Name</Form.Label>
                                        <Form.Control
                                            name="name"
                                            value={data.name}
                                            onChange={handleInput}
                                            required
                                            className="py-2"
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label className="fw-semibold">Email</Form.Label>
                                        <Form.Control
                                            type="email"
                                            name="email"
                                            value={data.email}
                                            onChange={handleInput}
                                            required
                                            className="py-2"
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Row className="g-4">
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label className="fw-semibold">Gender</Form.Label>
                                        <Form.Select
                                            name="gender"
                                            value={data.gender}
                                            onChange={handleInput}
                                            required
                                            className="py-2"
                                        >
                                            <option value="">Select</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </Form.Select>
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label className="fw-semibold">Looking For</Form.Label>
                                        <Form.Select
                                            name="searching_for"
                                            value={data.searching_for}
                                            onChange={handleInput}
                                            required
                                            className="py-2"
                                        >
                                            <option value="">Select</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="both">Both</option>
                                        </Form.Select>
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Row className="g-4">
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label className="fw-semibold">Date of Birth</Form.Label>
                                        <Form.Control
                                            type="date"
                                            name="birth_date"
                                            value={formatDate(data.birth_date)}
                                            onChange={handleInput}
                                            required
                                            className="py-2"
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label className="fw-semibold">Phone Number</Form.Label>
                                        <Form.Control
                                            type="tel"
                                            name="phone_number"
                                            value={data.phone_number}
                                            onChange={handleInput}
                                            required
                                            className="py-2"
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Form.Group className="mb-3">
                                <Form.Label className="fw-semibold">Location</Form.Label>
                                <Form.Control
                                    name="location"
                                    value={data.location}
                                    onChange={handleInput}
                                    required
                                    className="py-2"
                                />
                                <Form.Text className="text-white-50">City, Country</Form.Text>
                            </Form.Group>

                            <Form.Group className="mb-3">
                                <Form.Label className="fw-semibold">Bio</Form.Label>
                                <Form.Control
                                    as="textarea"
                                    name="bio"
                                    rows={4}
                                    value={data.bio}
                                    onChange={handleInput}
                                    minLength={50}
                                    required
                                    className="py-2"
                                />
                                <Form.Text className="text-white-50">
                                    At least 50 characters
                                </Form.Text>
                            </Form.Group>

                            <Row>
                                <Col md={4}>
                                    {/* Profile Picture Dropzone */}
                                    <Form.Group className="mb-4">
                                        <Form.Label className="fw-semibold">Profile Picture</Form.Label>
                                        <div
                                            {...getProfilePicRootProps()}
                                            className={`dropzone ${isProfilePicDragActive ? 'active' : ''} p-4 border-2 border-dashed rounded-3 text-center cursor-pointer`}
                                        >
                                            <input {...getProfilePicInputProps()} />
                                            {isProfilePicDragActive ? (
                                                <p className="mb-0">Drop the profile picture here...</p>
                                            ) : (
                                                <p className="mb-0">
                                                    Drag & drop a profile picture here, or click to select
                                                </p>
                                            )}
                                        </div>
                                        {profilePicPreview && (
                                            <div className="mt-3 text-center">
                                                <Image
                                                    src={profilePicPreview}
                                                    alt="Profile preview"
                                                    className="img-thumbnail rounded-circle"
                                                    style={{ width: '150px', height: '150px', objectFit: 'cover' }}
                                                />
                                                <Button
                                                    variant="outline-danger"
                                                    size="sm"
                                                    className="mt-2"
                                                    onClick={() => handleFile("profile_picture", null)}
                                                >
                                                    Remove
                                                </Button>
                                            </div>
                                        )}
                                        {!profilePicPreview && data.profile_picture && (
                                            <div className="mt-3 text-center">
                                                <Image
                                                    src={`/storage/${data.profile_picture}`}
                                                    alt="Current profile"
                                                    className="img-thumbnail rounded-circle"
                                                    style={{ width: '150px', height: '150px', objectFit: 'cover' }}
                                                />
                                            </div>
                                        )}
                                        <Form.Text className="text-white-50">Profile photo (JPG, PNG)</Form.Text>
                                    </Form.Group>
                                </Col>
                                <Col md={4}>
                                    {/* Gallery Dropzone */}
                                    <Form.Group className="mb-4">
                                        <Form.Label className="fw-semibold">Gallery (max 6 images)</Form.Label>
                                        <div
                                            {...getGalleryRootProps()}
                                            className={`dropzone ${isGalleryDragActive ? 'active' : ''} p-4 border-2 border-dashed rounded-3 text-center cursor-pointer`}
                                        >
                                            <input {...getGalleryInputProps()} />
                                            {isGalleryDragActive ? (
                                                <p className="mb-0">Drop the gallery images here...</p>
                                            ) : (
                                                <p className="mb-0">
                                                    Drag & drop gallery images here, or click to select
                                                </p>
                                            )}
                                        </div>

                                        {/* <div className="mt-3">
                                            <Row className="g-2">
                                                {data.gallery.map((img, idx) => (
                                                    <Col xs={6} sm={4} md={3} lg={2} key={`existing-${idx}`}>
                                                        <div className="position-relative">
                                                            <Image
                                                                src={`/storage/${img}`}
                                                                alt={`gallery-${idx}`}
                                                                className="img-thumbnail w-100"
                                                                style={{ height: '120px', objectFit: 'cover' }}
                                                            />
                                                            <Button
                                                                variant="danger"
                                                                size="sm"
                                                                className="position-absolute top-0 end-0 rounded-circle p-0"
                                                                style={{ width: '24px', height: '24px' }}
                                                                onClick={() => removeGalleryImage(idx, false)}
                                                            >
                                                                ×
                                                            </Button>
                                                        </div>
                                                    </Col>
                                                ))}
                                                {galleryPreviews.map((preview, idx) => (
                                                    <Col xs={6} sm={4} md={3} lg={2} key={`new-${idx}`}>
                                                        <div className="position-relative">
                                                            <Image
                                                                src={preview}
                                                                alt={`new-gallery-${idx}`}
                                                                className="img-thumbnail w-100"
                                                                style={{ height: '120px', objectFit: 'cover' }}
                                                            />
                                                            <Button
                                                                variant="danger"
                                                                size="sm"
                                                                className="position-absolute top-0 end-0 rounded-circle p-0"
                                                                style={{ width: '24px', height: '24px' }}
                                                                onClick={() => removeGalleryImage(idx, true)}
                                                            >
                                                                ×
                                                            </Button>
                                                        </div>
                                                    </Col>
                                                ))}
                                            </Row>
                                        </div> */}

                                        <Form.Text className="text-white-50">
                                            {data.gallery.length + data.new_gallery_files.length} of 6 images selected
                                        </Form.Text>
                                    </Form.Group>
                                </Col>
                                <Col md={4}>
                                    {/* Documents Dropzone */}
                                    <Form.Group className="mb-4">
                                        <Form.Label className="fw-semibold">Verification Documents</Form.Label>
                                        <div
                                            {...getDocsRootProps()}
                                            className={`dropzone ${isDocsDragActive ? 'active' : ''} p-4 border-2 border-dashed rounded-3 text-center cursor-pointer`}
                                        >
                                            <input {...getDocsInputProps()} />
                                            {isDocsDragActive ? (
                                                <p className="mb-0">Drop the documents here...</p>
                                            ) : (
                                                <p className="mb-0">
                                                    Drag & drop verification documents here, or click to select
                                                </p>
                                            )}
                                        </div>
                                        {data.verification_documents.length > 0 && (
                                            <div className="mt-3">
                                                <h6 className="fw-semibold">Selected Documents:</h6>
                                                <ul className="list-group">
                                                    {data.verification_documents.map((file, idx) => (
                                                        <li key={idx} className="list-group-item d-flex justify-content-between align-items-center">
                                                            <span>{file.name}</span>
                                                            <Button
                                                                variant="outline-danger"
                                                                size="sm"
                                                                onClick={() => removeDocument(idx)}
                                                            >
                                                                Remove
                                                            </Button>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}
                                        <Form.Text className="text-white-50">ID, passport, etc. (PDF, JPG, PNG)</Form.Text>
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing}
                                className="w-100 py-2 fw-semibold gradient-btn"
                            >
                                {processing ? (
                                    <>
                                        <Spinner animation="border" size="sm" className="me-2" />
                                        Saving...
                                    </>
                                ) : "Update Profile"}
                            </Button>
                        </Form>
                    </Card.Body>
                </Card>
            </Container>
        </AppLayout>
    );
}
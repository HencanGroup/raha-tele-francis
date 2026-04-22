import { useState, useEffect } from "react";
import { Button } from "react-bootstrap";
import { motion } from "framer-motion";
import { Heart } from "lucide-react";
import { FaHeart } from "react-icons/fa";
import axios from "axios";
import { toast } from "react-toastify";

const FavoriteButton = ({ escortId, initialIsFavorite = false, size = "sm", wrapperClass = "" }) => {
    const [isFavorite, setIsFavorite] = useState(initialIsFavorite);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setIsFavorite(initialIsFavorite);
    }, [initialIsFavorite]);

    const handleToggle = async (e) => {
        e?.preventDefault();
        e?.stopPropagation();

        if (loading) return;

        const previousState = isFavorite;
        setIsFavorite(!previousState);
        setLoading(true);

        try {
            const response = await axios.post(route("favorites.toggle"), {
                escort_id: escortId,
            });

            const newState = response.data.is_favorited;
            setIsFavorite(newState);
            toast.success(newState ? "Added to favorites" : "Removed from favorites");
        } catch (error) {
            console.error("Error toggling favorite:", error);
            setIsFavorite(previousState);
            toast.error("Failed to update favorite");
        } finally {
            setLoading(false);
        }
    };

    // console.log("FavoriteButton - isFavorite:", isFavorite, "loading:", loading, "escortId:", escortId);

    const sizeStyles = {
        sm: { width: "32px", height: "32px", iconSize: 14 },
        md: { width: "40px", height: "40px", iconSize: 18 },
        lg: { width: "48px", height: "48px", iconSize: 22 },
    };

    const { width, height, iconSize } = sizeStyles[size] || sizeStyles.sm;

    return (
        <motion.div
            className={wrapperClass}
            initial={{ scale: 0 }}
            animate={{ scale: 1 }}
            transition={{
                type: "spring",
                stiffness: 260,
                damping: 20,
            }}
            whileHover={{
                scale: 1.1,
            }}
            whileTap={{ scale: 0.9 }}
        >
            <Button
                variant={isFavorite ? "danger" : "light"}
                size="sm"
                className="rounded-circle p-0 d-flex align-items-center justify-content-center"
                style={{
                    width,
                    height,
                    padding: 0,
                }}
                title={isFavorite ? "Remove from favorites" : "Add to favorites"}
                onClick={handleToggle}
                disabled={loading}
            >
                {isFavorite ? (
                    <FaHeart size={iconSize} className="text-white" />
                ) : (
                    <motion.div
                        animate={{ scale: [1, 1.1, 1] }}
                        transition={{ repeat: Infinity, duration: 2, delay: 1 }}
                    >
                        <Heart
                            size={iconSize}
                            className="text-danger"
                            strokeWidth={2.5}
                        />
                    </motion.div>
                )}
            </Button>
        </motion.div>
    );
};

export default FavoriteButton;
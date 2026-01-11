import { Image } from "react-bootstrap";

export default function ApplicationLogo(props) {
    return (
        <Image
            src="/storage/images/logos/logo.png"
            alt="Logo"
            {...props}
            onError={(e) => {
                e.target.onerror = null; // Prevents infinite loop if the image fails to load
                e.target.src = "/images/logo-fallback.png"; // Fallback image
            }}
            onLoad={(e) => {
                e.target.style.display = "block"; // Ensure the image is displayed after loading
            }}
            onClick={() => {
                window.location.href = "/"; // Redirect to home page on click
            }}
            className={`logo ${props.className || ""}`}
            style={{
                ...props.style,
                cursor: "pointer",
                maxWidth: "50px",
                maxHeight: "50px",
            }}
        />
    );
}

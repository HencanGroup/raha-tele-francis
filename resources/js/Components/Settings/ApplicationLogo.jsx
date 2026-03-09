import { Image } from "react-bootstrap";

export default function ApplicationLogo(props) {
    return (
        <Image
            src="https://i.ibb.co/35QhMPTZ/raha-tele.png"
            alt="Logo"
            {...props}
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

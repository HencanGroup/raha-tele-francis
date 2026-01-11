import { useState } from "react";
import { Dropdown } from "react-bootstrap";

export default function ThemeToggle({ type = "" }) {
    const [lightMode, setDarkMode] = useState(
        document.body.classList.contains("light-mode")
    );

    const toggleTheme = () => {
        const newMode = !lightMode;
        setDarkMode(newMode);
        document.body.classList.toggle("light-mode", newMode);
        localStorage.setItem("lightMode", newMode);
    };

    return type === "dropdown" ? (
        <Dropdown.Item onClick={toggleTheme}>
            <i className={`bi ${lightMode ? "bi-sun" : "bi-moon"}`}></i>{" "}
            {lightMode ? "Turn on the Lights" : "Turn off the Lights"}
        </Dropdown.Item>
    ) : (
        <a href="#" onClick={toggleTheme}>
            <i className={`bi ${lightMode ? "bi-sun" : "bi-moon"}`}></i>{" "}
            {lightMode ? "Light" : "Dark"}
        </a>
    );
}

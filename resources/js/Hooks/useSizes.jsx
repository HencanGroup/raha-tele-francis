import { useEffect, useState } from "react";

export function useDivHeights(className) {
    const [heights, setHeights] = useState([]);

    useEffect(() => {
        if (!className) return;

        const elements = Array.from(document.getElementsByClassName(className));

        const updateHeights = () => {
            const newHeights = elements.map((el) =>
                Math.round(el.getBoundingClientRect().height)
            );
            setHeights(newHeights);
        };

        updateHeights();

        // Observe size changes
        const resizeObserver = new ResizeObserver(updateHeights);
        elements.forEach((el) => resizeObserver.observe(el));

        // Window resize fallback
        window.addEventListener("resize", updateHeights);

        return () => {
            resizeObserver.disconnect();
            window.removeEventListener("resize", updateHeights);
        };
    }, [className]);

    return heights;
}

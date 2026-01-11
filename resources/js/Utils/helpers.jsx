// /resources/js/Utils/helpers.jsx

/**
 * Debounce function to limit how often a function can be called
 * @param {Function} func - The function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} - Debounced function
 */
export const debounce = (func, wait = 300) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Format date to different formats
 * @param {string|Date} date - Date to format
 * @param {string} format - Format string (YYYY-MM-DD, DD-MM-YYYY, HH:mm, etc.)
 * @returns {string} - Formatted date string
 */
export const formatDate = (date, format = "DD-MM-YYYY") => {
    if (!date) return "";

    const d = new Date(date);
    if (isNaN(d.getTime())) return "";

    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    const hours = String(d.getHours()).padStart(2, "0");
    const minutes = String(d.getMinutes()).padStart(2, "0");
    const seconds = String(d.getSeconds()).padStart(2, "0");

    // Support multiple formats
    const formats = {
        "YYYY-MM-DD": `${year}-${month}-${day}`,
        "DD-MM-YYYY": `${day}-${month}-${year}`,
        "MM/DD/YYYY": `${month}/${day}/${year}`,
        "DD/MM/YYYY": `${day}/${month}/${year}`,
        "HH:mm": `${hours}:${minutes}`,
        "HH:mm:ss": `${hours}:${minutes}:${seconds}`,
        "DD-MM-YYYY HH:mm": `${day}-${month}-${year} ${hours}:${minutes}`,
        "YYYY-MM-DD HH:mm:ss": `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`,
        relative: getRelativeTime(date),
    };

    // Return specific format or use dynamic replacement
    if (formats[format]) {
        return formats[format];
    }

    // Fallback to dynamic replacement
    return format
        .replace("YYYY", year)
        .replace("MM", month)
        .replace("DD", day)
        .replace("HH", hours)
        .replace("mm", minutes)
        .replace("ss", seconds);
};

/**
 * Get relative time (e.g., "2 hours ago")
 * @param {string|Date} date - Date to compare
 * @returns {string} - Relative time string
 */
export const getRelativeTime = (date) => {
    if (!date) return "";

    const now = new Date();
    const d = new Date(date);
    const diffInSeconds = Math.floor((now - d) / 1000);

    if (diffInSeconds < 60) return "just now";
    if (diffInSeconds < 3600)
        return `${Math.floor(diffInSeconds / 60)} minutes ago`;
    if (diffInSeconds < 86400)
        return `${Math.floor(diffInSeconds / 3600)} hours ago`;
    if (diffInSeconds < 604800)
        return `${Math.floor(diffInSeconds / 86400)} days ago`;

    return formatDate(date, "DD-MM-YYYY");
};

/**
 * Format time for chat messages
 * @param {string} timeString - Time string to format
 * @returns {string} - Formatted time
 */
export const formatTime = (timeString) => {
    if (!timeString) return "";

    const date = new Date(timeString);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const messageDate = new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate()
    );

    const diffTime = now - date;
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0 && date >= today) {
        // Today - show time only
        return date.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });
    } else if (diffDays === 1) {
        // Yesterday
        return "Yesterday";
    } else if (diffDays < 7) {
        // Within a week - show day name
        return date.toLocaleDateString([], { weekday: "short" });
    } else {
        // Older - show date
        return date.toLocaleDateString([], {
            month: "short",
            day: "numeric",
        });
    }
};

/**
 * Format currency
 * @param {number} amount - Amount to format
 * @param {string} currency - Currency code (default: KES)
 * @returns {string} - Formatted currency string
 */
export const formatCurrency = (amount, currency = "KES") => {
    if (amount === null || amount === undefined) return "";

    const numAmount = parseFloat(amount);
    if (isNaN(numAmount)) return "";

    // Custom formatting for KES
    if (currency === "KES") {
        return `KES ${numAmount.toLocaleString("en-US", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    }

    // Default Intl formatting for other currencies
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numAmount);
};

/**
 * Capitalize first letter of string
 * @param {string} str - String to capitalize
 * @returns {string} - Capitalized string
 */
export const capitalize = (str) => {
    if (!str || typeof str !== "string") return "";
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
};

/**
 * Capitalize each word in a string
 * @param {string} str - String to capitalize
 * @returns {string} - Title-cased string
 */
export const capitalizeWords = (str) => {
    if (!str || typeof str !== "string") return "";
    return str
        .split(" ")
        .map(
            (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        )
        .join(" ");
};

/**
 * Hide part of phone number for privacy
 * @param {string} phone - Phone number to hide
 * @param {number} visibleStart - Number of visible digits at start
 * @param {number} visibleEnd - Number of visible digits at end
 * @returns {string} - Masked phone number
 */
export const hidePhoneNumber = (phone, visibleStart = 3, visibleEnd = 2) => {
    if (!phone || typeof phone !== "string") return "";

    const phoneStr = phone.toString();
    if (phoneStr.length <= visibleStart + visibleEnd) return phoneStr;

    return (
        phoneStr.slice(0, visibleStart) +
        "*".repeat(phoneStr.length - (visibleStart + visibleEnd)) +
        phoneStr.slice(-visibleEnd)
    );
};

/**
 * Get profile image URL
 * @param {object} profile - Profile object
 * @param {string} defaultImage - Default image URL
 * @returns {string} - Profile image URL
 */
export const getProfileImage = (
    profile,
    defaultImage = "/images/default-avatar.png"
) => {
    // If profile is null or undefined
    if (!profile) return defaultImage;

    // If profile has a direct profile_picture
    if (profile.profile_picture) {
        // Check if it's already a full URL
        if (profile.profile_picture.startsWith("http")) {
            return profile.profile_picture;
        }
        // Check if it starts with /storage
        if (profile.profile_picture.startsWith("/storage")) {
            return profile.profile_picture;
        }
        // Assume it's a relative path
        return `/storage/${profile.profile_picture}`;
    }

    // If profile has an avatar field
    if (profile.avatar) {
        if (profile.avatar.startsWith("http")) {
            return profile.avatar;
        }
        if (profile.avatar.startsWith("/storage")) {
            return profile.avatar;
        }
        return `/storage/${profile.avatar}`;
    }

    // Fallback to random avatar based on user ID or generate random
    const index = profile?.id || Math.floor(Math.random() * 99) + 1;
    const gender = profile?.gender?.toLowerCase() || "male";

    return `https://randomuser.me/api/portraits/${
        gender === "female" ? "women" : "men"
    }/${index}.jpg`;
};

/**
 * Get initials from name
 * @param {string} name - Full name
 * @returns {string} - Initials
 */
export const getInitials = (name) => {
    if (!name) return "?";

    const words = name.trim().split(/\s+/);
    if (words.length === 1) return words[0].charAt(0).toUpperCase();

    return (
        words[0].charAt(0) + words[words.length - 1].charAt(0)
    ).toUpperCase();
};

/**
 * Validate email address
 * @param {string} email - Email to validate
 * @returns {boolean} - True if email is valid
 */
export const isValidEmail = (email) => {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
};

/**
 * Truncate text with ellipsis
 * @param {string} text - Text to truncate
 * @param {number} maxLength - Maximum length
 * @returns {string} - Truncated text
 */
export const truncateText = (text, maxLength = 100) => {
    if (!text || typeof text !== "string") return "";
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + "...";
};

/**
 * Format file size to readable format
 * @param {number} bytes - File size in bytes
 * @returns {string} - Formatted file size
 */
export const formatFileSize = (bytes) => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB", "TB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

/**
 * Sleep/delay function for async operations
 * @param {number} ms - Milliseconds to sleep
 * @returns {Promise} - Promise that resolves after delay
 */
export const sleep = (ms) => {
    return new Promise((resolve) => setTimeout(resolve, ms));
};

/**
 * Generate unique ID
 * @param {number} length - Length of ID
 * @returns {string} - Unique ID
 */
export const generateId = (length = 8) => {
    return Math.random()
        .toString(36)
        .substring(2, 2 + length);
};

/**
 * Check if value is empty (null, undefined, empty string, empty array, empty object)
 * @param {any} value - Value to check
 * @returns {boolean} - True if empty
 */
export const isEmpty = (value) => {
    if (value === null || value === undefined) return true;
    if (typeof value === "string" && value.trim() === "") return true;
    if (Array.isArray(value) && value.length === 0) return true;
    if (typeof value === "object" && Object.keys(value).length === 0)
        return true;
    return false;
};

/**
 * Clone an object (simple deep clone)
 * @param {object} obj - Object to clone
 * @returns {object} - Cloned object
 */
export const cloneObject = (obj) => {
    return JSON.parse(JSON.stringify(obj));
};

/**
 * Remove duplicate objects from array based on property
 * @param {array} array - Array to deduplicate
 * @param {string} key - Property key to compare
 * @returns {array} - Deduplicated array
 */
export const removeDuplicates = (array, key = "id") => {
    if (!Array.isArray(array)) return [];
    return array.filter(
        (item, index, self) =>
            index === self.findIndex((t) => t[key] === item[key])
    );
};

export const formatPhoneNumber = (phone) => {
    let formatted = phone.replace(/\s+/g, "");
    if (formatted.startsWith("0")) {
        formatted = "254" + formatted.substring(1);
    } else if (formatted.startsWith("+254")) {
        formatted = formatted.substring(1);
    } else if (!formatted.startsWith("254")) {
        formatted = "254" + formatted;
    }
    return formatted;
};

export const validatePhoneNumber = (number) => {
    const regex = /^(?:254|\+254|0)?(7[0-9]|1[0-9])\d{7}$/;
    return regex.test(number.replace(/\s+/g, ""));
};

/**
 * Calculate coins based on amount (no packages)
 *
 * @param {number} amount - Amount paid (KSh)
 * @returns {number} coins to award
 */
export const calculateCoins = (amount) => {
    if (!amount || amount <= 0) return 0;

    let coins = 0;

    if (amount <= 100) {
        coins = amount * 1;
    } else if (amount <= 200) {
        coins = 100 + (amount - 100) * 1.5;
    } else if (amount <= 350) {
        coins = 250 + (amount - 200) * 1.55;
    } else if (amount <= 600) {
        coins = 500 + (amount - 350) * 1.6;
    } else {
        coins = 1000 + (amount - 600) * 2.0;
    }

    return Math.floor(coins);
};

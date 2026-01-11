import { useCallback } from "react";
import { toast } from "react-toastify";

export const useErrorToast = () => {
    const showErrorToast = useCallback((error) => {
        if (error.response?.data) {
            const { data } = error.response;

            if (data.errors) {
                Object.values(data.errors).forEach((errorArray) => {
                    errorArray.forEach((errorMessage) => {
                        toast.error(errorMessage);
                    });
                });
            } else if (data.message) {
                toast.error(data.message);
            } else {
                toast.error("An unexpected error occurred");
            }
        } else if (error.message) {
            toast.error(error.message);
        } else {
            toast.error("An unknown error occurred");
        }
    }, []);

    return { showErrorToast };
};

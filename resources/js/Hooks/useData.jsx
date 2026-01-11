import { useEffect, useState } from "react";

export default function useData() {
    const [counties, setCounties] = useState([]);
    const [towns, setTowns] = useState([]);
    const [allTowns, setAllTowns] = useState([]); // Store all towns initially
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchData = async (endpoint, setter) => {
        try {
            const response = await fetch(route(endpoint));
            if (!response.ok) {
                throw new Error(
                    `API request failed with status ${response.status}`
                );
            }
            const data = await response.json();
            setter(data);
            return { success: true, data };
        } catch (error) {
            console.error(`Error fetching ${endpoint}:`, error);
            setError(error.message || `Failed to fetch ${endpoint}`);
            return { success: false, error };
        }
    };

    const fetchAll = async () => {
        try {
            setIsLoading(true);
            setError(null);

            const promises = [
                fetchData("api.counties", setCounties),
                fetchData("api.towns", setAllTowns), // Fetch all towns
            ];

            const results = await Promise.allSettled(promises);

            // Log any failed requests for debugging
            results.forEach((result, index) => {
                if (result.status === "rejected") {
                    console.error(`Request ${index} failed:`, result.reason);
                }
            });

            // Initially set towns to all towns
            if (results[1]?.value?.success) {
                setTowns(results[1].value.data);
            }
        } catch (error) {
            console.error("Error in fetchAll:", error);
            setError(error.message || "Failed to fetch all data");
        } finally {
            setIsLoading(false);
        }
    };

    // Filter towns by county
    // In your useData hook, update the filterTownsByCounty function:
    const filterTownsByCounty = (countyId) => {
        if (!countyId) {
            setTowns(allTowns);
            return;
        }

        // Convert countyId to number for comparison
        const countyIdNum = parseInt(countyId);
        const filtered = allTowns.filter(
            (town) =>
                town.county_id === countyIdNum ||
                town.county_id?.toString() === countyId
        );

        // Update the towns state
        setTowns(filtered);
    };

    // Individual refresh functions
    const refreshCounties = () => fetchData("api.counties", setCounties);
    const refreshTowns = () => fetchData("api.towns", setAllTowns);
    const refreshAllTowns = () => {
        fetchData("api.towns", (data) => {
            setAllTowns(data);
            setTowns(data); // Update both
        });
    };

    useEffect(() => {
        fetchAll();
    }, []);

    return {
        // Data states
        counties,
        towns,
        allTowns,

        // Loading states
        isLoading,
        error,

        // Refresh functions
        refreshCounties,
        refreshTowns,
        refreshAllTowns,

        // Filter function
        filterTownsByCounty,

        // Bulk refresh
        refreshAll: fetchAll,
    };
}

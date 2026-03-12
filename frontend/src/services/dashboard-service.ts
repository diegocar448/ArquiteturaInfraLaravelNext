import { apiClient } from "@/lib/api";
import type { DashboardMetrics } from "@/types/dashboard";

export async function getDashboardMetrics(): Promise<DashboardMetrics> {
    const response = await apiClient<{ data: DashboardMetrics }>(
        "/v1/dashboard/metrics",
    );
    return response.data;
}
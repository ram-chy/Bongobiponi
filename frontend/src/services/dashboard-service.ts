import apiClient from "@/lib/axios";
import type { DashboardData } from "@/types/dashboard";

export const dashboardService = {
  getSummary: () =>
    apiClient.get<{ data: DashboardData }>("/dashboard/summary"),
};

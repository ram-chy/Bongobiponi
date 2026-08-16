"use client";

import { useQuery } from "@tanstack/react-query";
import { dashboardService } from "@/services/dashboard-service";

export function useDashboardSummary() {
  return useQuery({
    queryKey: ["/dashboard/summary"],
    queryFn: async () => {
      const response = await dashboardService.getSummary();
      return response.data.data;
    },
  });
}

"use client";

import { useQuery } from "@tanstack/react-query";
import apiClient from "@/lib/axios";
import type { Customer } from "@/types/customer";

export function useCustomerSearch(search: string) {
  return useQuery({
    queryKey: ["customers", "search", search],
    queryFn: async () => {
      const response = await apiClient.get("/customers", {
        params: { search, per_page: 20 },
      });
      return response.data.data as Customer[];
    },
    enabled: search.length > 0,
  });
}

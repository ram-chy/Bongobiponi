"use client";

import { useQuery } from "@tanstack/react-query";
import { supplierService } from "@/services/supplier-service";

interface SupplierListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
  status?: string;
}

export function useSupplierList(params: SupplierListParams) {
  return useQuery({
    queryKey: ["/suppliers", params],
    queryFn: async () => {
      const response = await supplierService.list(
        params as Record<string, unknown>
      );
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

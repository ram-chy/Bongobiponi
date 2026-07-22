"use client";

import { useQuery } from "@tanstack/react-query";
import { purchaseService } from "@/services/purchase-service";

interface PurchaseListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
  status?: string;
  supplier_id?: number;
  purchase_type?: string;
}

export function usePurchaseList(params: PurchaseListParams) {
  return useQuery({
    queryKey: ["/purchases", params],
    queryFn: async () => {
      const response = await purchaseService.list(
        params as Record<string, unknown>
      );
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

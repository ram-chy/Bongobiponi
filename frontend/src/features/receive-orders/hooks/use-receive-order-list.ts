"use client";

import { useQuery } from "@tanstack/react-query";
import { receiveOrderService } from "@/services/receive-order-service";

interface ReceiveOrderListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
  status?: string;
  supplier_id?: number;
}

export function useReceiveOrderList(params: ReceiveOrderListParams) {
  return useQuery({
    queryKey: ["/receive-orders", params],
    queryFn: async () => {
      const response = await receiveOrderService.list(
        params as Record<string, unknown>
      );
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

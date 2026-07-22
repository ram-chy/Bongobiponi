"use client";

import { useQuery } from "@tanstack/react-query";
import { salesOrderService } from "@/services/sales-order-service";

export function useSalesOrderList(params: Record<string, unknown>) {
  return useQuery({
    queryKey: ["/sales-orders", params],
    queryFn: async () => {
      const response = await salesOrderService.list(params);
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

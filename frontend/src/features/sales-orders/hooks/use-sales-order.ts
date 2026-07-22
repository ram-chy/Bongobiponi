"use client";

import { useQuery } from "@tanstack/react-query";
import { salesOrderService } from "@/services/sales-order-service";
import type { SalesOrder } from "@/types/sales-order";

export function useSalesOrder(id: number) {
  return useQuery({
    queryKey: ["/sales-orders", id],
    queryFn: async () => {
      const response = await salesOrderService.get(id);
      return response.data.data as SalesOrder;
    },
    enabled: !!id,
  });
}

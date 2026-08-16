"use client";

import { useQuery } from "@tanstack/react-query";
import { orderService } from "@/services/order-service";
import type { OrderStatusHistory } from "@/types/order";

export function useOrderStatusHistory(id: number) {
  return useQuery({
    queryKey: ["/orders", id, "status-history"],
    queryFn: async () => {
      const response = await orderService.getStatusHistory(id);
      return response.data.data as OrderStatusHistory[];
    },
    enabled: !!id,
  });
}

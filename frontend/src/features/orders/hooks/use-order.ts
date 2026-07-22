"use client";

import { useQuery } from "@tanstack/react-query";
import { orderService } from "@/services/order-service";
import type { Order } from "@/types/order";

export function useOrder(id: number) {
  return useQuery({
    queryKey: ["/orders", id],
    queryFn: async () => {
      const response = await orderService.get(id);
      return response.data.data as Order;
    },
    enabled: !!id,
  });
}

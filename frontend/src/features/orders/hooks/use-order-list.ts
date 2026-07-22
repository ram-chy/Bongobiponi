"use client";

import { useQuery } from "@tanstack/react-query";
import { orderService } from "@/services/order-service";

export function useOrderList(params: Record<string, unknown>) {
  return useQuery({
    queryKey: ["/orders", params],
    queryFn: async () => {
      const response = await orderService.list(params);
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

"use client";

import { useQuery } from "@tanstack/react-query";
import { receiveOrderService } from "@/services/receive-order-service";
import type { ReceiveOrder } from "@/types/receive-order";

export function useReceiveOrder(id: number) {
  return useQuery({
    queryKey: ["/receive-orders", id],
    queryFn: async () => {
      const response = await receiveOrderService.get(id);
      return response.data.data;
    },
    enabled: !!id,
  });
}

"use client";

import { useQuery } from "@tanstack/react-query";
import { purchaseService } from "@/services/purchase-service";

export function usePurchase(id: number) {
  return useQuery({
    queryKey: ["/purchases", id],
    queryFn: async () => {
      const response = await purchaseService.get(id);
      return response.data.data;
    },
    enabled: !!id,
  });
}

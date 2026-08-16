"use client";

import { useQuery } from "@tanstack/react-query";
import { orderService } from "@/services/order-service";
import type { OrderAvailability } from "@/types/order";

export function useOrderAvailability(id: number) {
  return useQuery({
    queryKey: ["/orders", id, "availability"],
    queryFn: async () => {
      const response = await orderService.getAvailability(id);
      return response.data.data as OrderAvailability;
    },
    enabled: !!id,
  });
}

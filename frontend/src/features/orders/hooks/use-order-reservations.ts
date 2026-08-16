"use client";

import { useQuery } from "@tanstack/react-query";
import { orderService } from "@/services/order-service";
import type { OrderReservation } from "@/types/order";

export function useOrderReservations(id: number) {
  return useQuery({
    queryKey: ["/orders", id, "reservations"],
    queryFn: async () => {
      const response = await orderService.getReservations(id);
      return response.data.data as OrderReservation[];
    },
    enabled: !!id,
  });
}
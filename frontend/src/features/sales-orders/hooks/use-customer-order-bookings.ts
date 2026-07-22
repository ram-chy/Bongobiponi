"use client";

import { useQuery } from "@tanstack/react-query";
import { orderService } from "@/services/order-service";
import type { Order } from "@/types/order";

export function useCustomerOrderBookings(customerId: number | null) {
  return useQuery({
    queryKey: ["/orders", "customer", customerId],
    queryFn: async () => {
      const response = await orderService.list({
        customer_id: customerId,
        per_page: 100,
      });
      return response.data.data as Order[];
    },
    enabled: !!customerId,
  });
}

export function useOrderWithItems(orderId: number | null) {
  return useQuery({
    queryKey: ["/orders", orderId, "with-items"],
    queryFn: async () => {
      const response = await orderService.get(orderId!);
      return response.data.data as Order;
    },
    enabled: !!orderId,
  });
}

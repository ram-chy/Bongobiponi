"use client";

import { useQuery } from "@tanstack/react-query";
import { paymentService } from "@/services/payment-service";
import type { Payment } from "@/types/payment";

export function usePayment(id: number) {
  return useQuery({
    queryKey: ["/payments", id],
    queryFn: async () => {
      const response = await paymentService.get(id);
      return response.data.data as Payment;
    },
    enabled: !!id,
  });
}

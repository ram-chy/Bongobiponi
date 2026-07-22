"use client";

import { useQuery } from "@tanstack/react-query";
import { paymentService } from "@/services/payment-service";
import type { PaymentListParams } from "@/types/payment";

export function usePaymentList(params: PaymentListParams) {
  return useQuery({
    queryKey: ["/payments", params],
    queryFn: async () => {
      const response = await paymentService.list(params as Record<string, unknown>);
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

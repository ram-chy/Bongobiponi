"use client";

import { useQuery } from "@tanstack/react-query";
import { paymentService } from "@/services/payment-service";
import type { DueInvoice } from "@/types/payment";

export function useCustomerDueInvoices(customerId: number | null) {
  return useQuery({
    queryKey: ["/customers", customerId, "due-invoices"],
    queryFn: async () => {
      const response = await paymentService.getCustomerDueInvoices(customerId!);
      return response.data.data as DueInvoice[];
    },
    enabled: !!customerId,
  });
}

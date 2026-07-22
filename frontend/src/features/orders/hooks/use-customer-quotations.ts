"use client";

import { useQuery } from "@tanstack/react-query";
import { quotationService } from "@/services/quotation-service";
import type { Quotation } from "@/types/quotation";

export function useCustomerQuotations(customerId: number | null) {
  return useQuery({
    queryKey: ["/quotations", "customer", customerId],
    queryFn: async () => {
      const response = await quotationService.list({
        customer_id: customerId,
        per_page: 100,
      });
      return response.data.data as Quotation[];
    },
    enabled: !!customerId,
  });
}

export function useQuotationWithItems(quotationId: number | null) {
  return useQuery({
    queryKey: ["/quotations", quotationId, "with-items"],
    queryFn: async () => {
      const response = await quotationService.get(quotationId!);
      return response.data.data as Quotation;
    },
    enabled: !!quotationId,
  });
}

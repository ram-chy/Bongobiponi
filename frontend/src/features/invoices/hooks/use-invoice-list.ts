"use client";

import { useQuery } from "@tanstack/react-query";
import { invoiceService } from "@/services/invoice-service";
import type { InvoiceListParams } from "@/types/invoice";

export function useInvoiceList(params: InvoiceListParams) {
  return useQuery({
    queryKey: ["/invoices", params],
    queryFn: async () => {
      const response = await invoiceService.list(params as Record<string, unknown>);
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

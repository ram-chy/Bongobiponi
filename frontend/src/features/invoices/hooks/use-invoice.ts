"use client";

import { useQuery } from "@tanstack/react-query";
import { invoiceService } from "@/services/invoice-service";
import type { Invoice } from "@/types/invoice";

export function useInvoice(id: number) {
  return useQuery({
    queryKey: ["/invoices", id],
    queryFn: async () => {
      const response = await invoiceService.get(id);
      return response.data.data as Invoice;
    },
    enabled: !!id,
  });
}

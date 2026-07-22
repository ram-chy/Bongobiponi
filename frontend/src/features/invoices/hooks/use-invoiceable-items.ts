"use client";

import { useQuery } from "@tanstack/react-query";
import { invoiceService } from "@/services/invoice-service";
import type { InvoiceableItem } from "@/types/invoice";

export function useInvoiceableItems(deliveryChallanId: number | null) {
  return useQuery({
    queryKey: ["/delivery-challans", deliveryChallanId, "invoiceable-items"],
    queryFn: async () => {
      const response = await invoiceService.getInvoiceableItems(deliveryChallanId!);
      return response.data.data as InvoiceableItem[];
    },
    enabled: !!deliveryChallanId,
  });
}

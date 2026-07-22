"use client";

import { useQuery } from "@tanstack/react-query";
import { quotationService } from "@/services/quotation-service";
import type { Quotation } from "@/types/quotation";

export function useQuotation(id: number) {
  return useQuery({
    queryKey: ["/quotations", id],
    queryFn: async () => {
      const response = await quotationService.get(id);
      return response.data.data as Quotation;
    },
    enabled: !!id,
  });
}

"use client";

import { useQuery } from "@tanstack/react-query";
import { quotationService } from "@/services/quotation-service";

export function useQuotationList(params: Record<string, unknown>) {
  return useQuery({
    queryKey: ["/quotations", params],
    queryFn: async () => {
      const response = await quotationService.list(params);
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

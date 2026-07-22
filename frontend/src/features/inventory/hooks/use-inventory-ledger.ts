"use client";

import { useQuery } from "@tanstack/react-query";
import { inventoryService } from "@/services/inventory-service";

interface LedgerParams {
  bookId: number;
  date_from?: string;
  date_to?: string;
}

export function useInventoryLedger({ bookId, date_from, date_to }: LedgerParams) {
  return useQuery({
    queryKey: ["/inventory/ledger", bookId, { date_from, date_to }],
    queryFn: async () => {
      const response = await inventoryService.getLedger(bookId, {
        date_from,
        date_to,
      });
      return response.data.data;
    },
    enabled: !!bookId,
  });
}

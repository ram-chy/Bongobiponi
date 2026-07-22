"use client";

import { useQuery } from "@tanstack/react-query";
import { inventoryService } from "@/services/inventory-service";

export function useStock(bookId: number) {
  return useQuery({
    queryKey: ["/inventory/stock", bookId],
    queryFn: async () => {
      const response = await inventoryService.getStock(bookId);
      return response.data.data;
    },
    enabled: !!bookId,
  });
}

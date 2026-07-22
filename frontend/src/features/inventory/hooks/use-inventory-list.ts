"use client";

import { useQuery } from "@tanstack/react-query";
import { inventoryService } from "@/services/inventory-service";

interface InventoryListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
  book_id?: number;
  transaction_type?: string;
  reference_type?: string;
  date_from?: string;
  date_to?: string;
}

export function useInventoryList(params: InventoryListParams) {
  return useQuery({
    queryKey: ["/inventory", params],
    queryFn: async () => {
      const response = await inventoryService.list(
        params as Record<string, unknown>
      );
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

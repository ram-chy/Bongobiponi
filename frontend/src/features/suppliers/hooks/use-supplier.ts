"use client";

import { useQuery } from "@tanstack/react-query";
import { supplierService } from "@/services/supplier-service";
import type { Supplier } from "@/types/supplier";

export function useSupplier(id: number) {
  return useQuery({
    queryKey: ["/suppliers", id],
    queryFn: async () => {
      const response = await supplierService.get(id);
      return response.data.data;
    },
    enabled: !!id,
  });
}

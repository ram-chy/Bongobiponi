"use client";

import { useQuery } from "@tanstack/react-query";
import { customerService } from "@/services/customer-service";
import type { Customer } from "@/types/customer";

export function useCustomer(id: number) {
  return useQuery({
    queryKey: ["/customers", id],
    queryFn: async () => {
      const response = await customerService.get(id);
      return response.data.data;
    },
    enabled: !!id,
  });
}

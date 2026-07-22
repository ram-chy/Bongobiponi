"use client";

import { useQuery } from "@tanstack/react-query";
import { deliveryChallanService } from "@/services/delivery-challan-service";
import type { DeliveryChallan } from "@/types/delivery-challan";

export function useDeliveryChallan(id: number) {
  return useQuery({
    queryKey: ["/delivery-challans", id],
    queryFn: async () => {
      const response = await deliveryChallanService.get(id);
      return response.data.data as DeliveryChallan;
    },
    enabled: !!id,
  });
}

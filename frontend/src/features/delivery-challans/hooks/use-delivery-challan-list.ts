"use client";

import { useQuery } from "@tanstack/react-query";
import { deliveryChallanService } from "@/services/delivery-challan-service";
import type { DeliveryChallanListParams } from "@/types/delivery-challan";

export function useDeliveryChallanList(params: DeliveryChallanListParams) {
  return useQuery({
    queryKey: ["/delivery-challans", params],
    queryFn: async () => {
      const response = await deliveryChallanService.list(params as Record<string, unknown>);
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

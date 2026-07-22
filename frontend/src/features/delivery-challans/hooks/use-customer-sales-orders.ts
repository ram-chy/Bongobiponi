"use client";

import { useQuery } from "@tanstack/react-query";
import { deliveryChallanService } from "@/services/delivery-challan-service";
import { salesOrderService } from "@/services/sales-order-service";
import type { SalesOrder } from "@/types/sales-order";

export function useCustomerSalesOrders(customerId: number | null) {
  return useQuery({
    queryKey: ["/sales-orders", "customer", customerId],
    queryFn: async () => {
      const response = await deliveryChallanService.getSalesOrderItems(customerId!);
      return response.data.data as SalesOrder[];
    },
    enabled: !!customerId,
  });
}

export function useSalesOrderWithItems(salesOrderId: number | null) {
  return useQuery({
    queryKey: ["/sales-orders", salesOrderId, "with-items"],
    queryFn: async () => {
      const response = await salesOrderService.get(salesOrderId!);
      return response.data.data as SalesOrder;
    },
    enabled: !!salesOrderId,
  });
}

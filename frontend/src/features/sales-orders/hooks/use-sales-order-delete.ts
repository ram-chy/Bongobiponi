"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { salesOrderService } from "@/services/sales-order-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useSalesOrderDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => salesOrderService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/sales-orders"] });
      toast.success("Sales Order deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Failed to delete sales order");
      } else {
        toast.error("Failed to delete sales order");
      }
    },
  });
}

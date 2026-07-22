"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { salesOrderService } from "@/services/sales-order-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseSalesOrderFormOptions {
  id?: number;
}

export function useSalesOrderForm({ id }: UseSalesOrderFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id ? salesOrderService.update(id, data as never) : salesOrderService.create(data as never),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/sales-orders"] });
      toast.success(
        id
          ? "Sales Order updated successfully"
          : "Sales Order created successfully"
      );
      router.push("/sales-orders");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

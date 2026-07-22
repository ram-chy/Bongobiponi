"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { receiveOrderService } from "@/services/receive-order-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseReceiveOrderFormOptions {
  id?: number;
}

export function useReceiveOrderForm({ id }: UseReceiveOrderFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id
        ? receiveOrderService.update(id, data)
        : receiveOrderService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/receive-orders"] });
      toast.success(
        id
          ? "Receive order updated successfully"
          : "Receive order created successfully"
      );
      router.push("/receive-orders");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return error.response.data;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { orderService } from "@/services/order-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseOrderFormOptions {
  id?: number;
}

export function useOrderForm({ id }: UseOrderFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id ? orderService.update(id, data as never) : orderService.create(data as never),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/orders"] });
      toast.success(
        id
          ? "Order updated successfully"
          : "Order created successfully"
      );
      router.push("/orders");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

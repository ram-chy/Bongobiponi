"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { purchaseService } from "@/services/purchase-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function usePurchaseDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => purchaseService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/purchases"] });
      toast.success("Purchase deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(
          error.response?.data?.message || "Failed to delete purchase"
        );
      } else {
        toast.error("Failed to delete purchase");
      }
    },
  });
}

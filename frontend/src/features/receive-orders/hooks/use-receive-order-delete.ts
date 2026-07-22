"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { receiveOrderService } from "@/services/receive-order-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useReceiveOrderDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => receiveOrderService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/receive-orders"] });
      toast.success("Receive order deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(
          error.response?.data?.message || "Failed to delete receive order"
        );
      } else {
        toast.error("Failed to delete receive order");
      }
    },
  });
}

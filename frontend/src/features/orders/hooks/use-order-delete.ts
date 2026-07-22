"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { orderService } from "@/services/order-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useOrderDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => orderService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/orders"] });
      toast.success("Order deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Failed to delete order");
      } else {
        toast.error("Failed to delete order");
      }
    },
  });
}

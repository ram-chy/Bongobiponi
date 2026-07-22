"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { deliveryChallanService } from "@/services/delivery-challan-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useDeliveryChallanDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => deliveryChallanService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/delivery-challans"] });
      toast.success("Delivery Challan deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Failed to delete");
      } else {
        toast.error("Failed to delete");
      }
    },
  });
}

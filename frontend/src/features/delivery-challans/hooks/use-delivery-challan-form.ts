"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { deliveryChallanService } from "@/services/delivery-challan-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseDeliveryChallanFormOptions {
  id?: number;
}

export function useDeliveryChallanForm({ id }: UseDeliveryChallanFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id
        ? deliveryChallanService.update(id, data as never)
        : deliveryChallanService.create(data as never),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/delivery-challans"] });
      toast.success(
        id
          ? "Delivery Challan updated successfully"
          : "Delivery Challan created successfully"
      );
      router.push("/delivery-challans");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

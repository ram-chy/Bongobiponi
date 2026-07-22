"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { paymentService } from "@/services/payment-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function usePaymentDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => paymentService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/payments"] });
      queryClient.invalidateQueries({ queryKey: ["/customers"] });
      toast.success("Payment deleted successfully");
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

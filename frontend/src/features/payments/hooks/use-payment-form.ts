"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { paymentService } from "@/services/payment-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UsePaymentFormOptions {
  id?: number;
}

export function usePaymentForm({ id }: UsePaymentFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id
        ? paymentService.update(id, data)
        : paymentService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/payments"] });
      queryClient.invalidateQueries({ queryKey: ["/customers"] });
      toast.success(
        id
          ? "Payment updated successfully"
          : "Payment created successfully"
      );
      router.push("/payments");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { purchaseService } from "@/services/purchase-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UsePurchaseFormOptions {
  id?: number;
}

export function usePurchaseForm({ id }: UsePurchaseFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id
        ? purchaseService.update(id, data)
        : purchaseService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/purchases"] });
      toast.success(
        id
          ? "Purchase updated successfully"
          : "Purchase created successfully"
      );
      router.push("/purchases");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return error.response.data;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

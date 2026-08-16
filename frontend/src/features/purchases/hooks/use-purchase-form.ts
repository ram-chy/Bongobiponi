"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { purchaseService } from "@/services/purchase-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UsePurchaseFormOptions {
  id?: number;
  onCreated?: (purchaseId: number) => void;
}

export function usePurchaseForm({ id, onCreated }: UsePurchaseFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id
        ? purchaseService.update(id, data)
        : purchaseService.create(data),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ["/purchases"] });
      toast.success(
        id
          ? "Purchase updated successfully"
          : "Purchase created successfully"
      );

      const createdId = response?.data?.data?.id as number | undefined;

      if (!id && createdId && onCreated) {
        onCreated(createdId);
        return;
      }

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

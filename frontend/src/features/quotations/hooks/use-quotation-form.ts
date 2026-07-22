"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { quotationService } from "@/services/quotation-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseQuotationFormOptions {
  id?: number;
}

export function useQuotationForm({ id }: UseQuotationFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id ? quotationService.update(id, data as never) : quotationService.create(data as never),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/quotations"] });
      toast.success(
        id
          ? "Quotation updated successfully"
          : "Quotation created successfully"
      );
      router.push("/quotations");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { quotationService } from "@/services/quotation-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useQuotationDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => quotationService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/quotations"] });
      toast.success("Quotation deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Failed to delete quotation");
      } else {
        toast.error("Failed to delete quotation");
      }
    },
  });
}

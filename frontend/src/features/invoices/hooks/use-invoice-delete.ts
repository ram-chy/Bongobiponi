"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { invoiceService } from "@/services/invoice-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useInvoiceDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => invoiceService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/invoices"] });
      toast.success("Invoice deleted successfully");
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

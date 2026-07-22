"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { invoiceService } from "@/services/invoice-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseInvoiceFormOptions {
  id?: number;
}

export function useInvoiceForm({ id }: UseInvoiceFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id
        ? invoiceService.update(id, data as never)
        : invoiceService.create(data as never),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/invoices"] });
      toast.success(
        id
          ? "Invoice updated successfully"
          : "Invoice created successfully"
      );
      router.push("/invoices");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

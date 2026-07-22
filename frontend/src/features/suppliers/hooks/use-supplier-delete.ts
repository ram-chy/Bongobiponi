"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { supplierService } from "@/services/supplier-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useSupplierDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => supplierService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/suppliers"] });
      toast.success("Supplier deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Failed to delete supplier");
      } else {
        toast.error("Failed to delete supplier");
      }
    },
  });
}

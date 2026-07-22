"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { supplierService } from "@/services/supplier-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseSupplierFormOptions {
  id?: number;
}

export function useSupplierForm({ id }: UseSupplierFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id ? supplierService.update(id, data) : supplierService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/suppliers"] });
      toast.success(
        id ? "Supplier updated successfully" : "Supplier created successfully"
      );
      router.push("/suppliers");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return error.response.data;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

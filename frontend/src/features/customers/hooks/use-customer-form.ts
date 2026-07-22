"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { customerService } from "@/services/customer-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseCustomerFormOptions {
  id?: number;
}

export function useCustomerForm({ id }: UseCustomerFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id ? customerService.update(id, data) : customerService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/customers"] });
      toast.success(
        id ? "Customer updated successfully" : "Customer created successfully"
      );
      router.push("/customers");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return error.response.data;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

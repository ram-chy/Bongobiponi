"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { expenseCategoryService } from "@/services/expense-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useExpenseCategoryForm({ id }: { id?: number } = {}) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id
        ? expenseCategoryService.update(id, data)
        : expenseCategoryService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/expense-categories"] });
      queryClient.invalidateQueries({ queryKey: ["/expenses"] });
      toast.success(
        id
          ? "Category updated successfully"
          : "Category created successfully"
      );
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { expenseService } from "@/services/expense-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useExpenseDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => expenseService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/expenses"] });
      toast.success("Expense deleted successfully");
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

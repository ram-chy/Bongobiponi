"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { expenseService } from "@/services/expense-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useExpenseForm({ id }: { id?: number } = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: FormData) =>
      id
        ? expenseService.update(id, data)
        : expenseService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/expenses"] });
      toast.success(
        id
          ? "Expense updated successfully"
          : "Expense created successfully"
      );
      router.push("/expenses");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

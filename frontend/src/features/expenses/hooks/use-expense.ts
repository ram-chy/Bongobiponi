"use client";

import { useQuery } from "@tanstack/react-query";
import { expenseService } from "@/services/expense-service";
import type { Expense } from "@/types/expense";

export function useExpense(id: number) {
  return useQuery({
    queryKey: ["/expenses", id],
    queryFn: async () => {
      const response = await expenseService.get(id);
      return response.data.data as Expense;
    },
    enabled: !!id,
  });
}

"use client";

import { useQuery } from "@tanstack/react-query";
import { expenseService } from "@/services/expense-service";
import type { ExpenseListParams } from "@/types/expense";

export function useExpenseList(params: ExpenseListParams) {
  return useQuery({
    queryKey: ["/expenses", params],
    queryFn: async () => {
      const response = await expenseService.list(params as Record<string, unknown>);
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

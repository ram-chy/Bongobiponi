"use client";

import { useQuery } from "@tanstack/react-query";
import { expenseCategoryService } from "@/services/expense-service";
import type { ExpenseCategoryListParams } from "@/types/expense";

export function useExpenseCategoryList(params: ExpenseCategoryListParams) {
  return useQuery({
    queryKey: ["/expense-categories", params],
    queryFn: async () => {
      const response = await expenseCategoryService.list(params as Record<string, unknown>);
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

"use client";

import { useQuery } from "@tanstack/react-query";
import { categoryService } from "@/services/category-service";
import type { Category } from "@/types/category";

export function useCategory(id: number) {
  return useQuery({
    queryKey: ["/categories", id],
    queryFn: async () => {
      const response = await categoryService.get(id);
      return response.data.data;
    },
    enabled: !!id,
  });
}

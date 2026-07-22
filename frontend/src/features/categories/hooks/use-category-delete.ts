"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { categoryService } from "@/services/category-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useCategoryDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => categoryService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/categories"] });
      toast.success("Category deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(
          error.response?.data?.message || "Failed to delete category"
        );
      } else {
        toast.error("Failed to delete category");
      }
    },
  });
}

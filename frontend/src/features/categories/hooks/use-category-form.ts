"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { categoryService } from "@/services/category-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseCategoryFormOptions {
  id?: number;
}

export function useCategoryForm({ id }: UseCategoryFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id ? categoryService.update(id, data) : categoryService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/categories"] });
      toast.success(
        id ? "Category updated successfully" : "Category created successfully"
      );
      router.push("/categories");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return error.response.data;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

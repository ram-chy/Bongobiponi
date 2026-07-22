"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { bookService } from "@/services/book-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseBookFormOptions {
  id?: number;
}

export function useBookForm({ id }: UseBookFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id ? bookService.update(id, data) : bookService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/books"] });
      toast.success(
        id ? "Book updated successfully" : "Book created successfully"
      );
      router.push("/books");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        toast.error("Please fix the validation errors below.");
        return error.response.data;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

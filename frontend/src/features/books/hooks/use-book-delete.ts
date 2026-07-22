"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { bookService } from "@/services/book-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useBookDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => bookService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/books"] });
      toast.success("Book deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(
          error.response?.data?.message || "Failed to delete book"
        );
      } else {
        toast.error("Failed to delete book");
      }
    },
  });
}

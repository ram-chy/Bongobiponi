"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { authorService } from "@/services/author-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useAuthorDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => authorService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/authors"] });
      toast.success("Author deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Failed to delete author");
      } else {
        toast.error("Failed to delete author");
      }
    },
  });
}

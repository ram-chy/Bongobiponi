"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { authorService } from "@/services/author-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UseAuthorFormOptions {
  id?: number;
}

export function useAuthorForm({ id }: UseAuthorFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id ? authorService.update(id, data) : authorService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/authors"] });
      toast.success(
        id ? "Author updated successfully" : "Author created successfully"
      );
      router.push("/authors");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return error.response.data;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

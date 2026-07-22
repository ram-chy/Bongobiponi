"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { publisherService } from "@/services/publisher-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

interface UsePublisherFormOptions {
  id?: number;
}

export function usePublisherForm({ id }: UsePublisherFormOptions = {}) {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      id ? publisherService.update(id, data) : publisherService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/publishers"] });
      toast.success(
        id ? "Publisher updated successfully" : "Publisher created successfully"
      );
      router.push("/publishers");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        return error.response.data;
      }
      toast.error("An unexpected error occurred");
    },
  });
}

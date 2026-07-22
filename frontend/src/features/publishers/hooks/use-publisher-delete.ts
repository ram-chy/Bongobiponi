"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { publisherService } from "@/services/publisher-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function usePublisherDelete() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => publisherService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/publishers"] });
      toast.success("Publisher deleted successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Failed to delete publisher");
      } else {
        toast.error("Failed to delete publisher");
      }
    },
  });
}

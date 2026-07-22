"use client";

import { useQuery } from "@tanstack/react-query";
import { publisherService } from "@/services/publisher-service";
import type { Publisher } from "@/types/publisher";

export function usePublisher(id: number) {
  return useQuery({
    queryKey: ["/publishers", id],
    queryFn: async () => {
      const response = await publisherService.get(id);
      return response.data.data;
    },
    enabled: !!id,
  });
}

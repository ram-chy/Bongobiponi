"use client";

import { useQuery } from "@tanstack/react-query";
import { authorService } from "@/services/author-service";
import type { Author } from "@/types/author";

export function useAuthor(id: number) {
  return useQuery({
    queryKey: ["/authors", id],
    queryFn: async () => {
      const response = await authorService.get(id);
      return response.data.data;
    },
    enabled: !!id,
  });
}

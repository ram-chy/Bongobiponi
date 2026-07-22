"use client";

import { useQuery } from "@tanstack/react-query";
import { bookService } from "@/services/book-service";
import type { Book } from "@/types/book";

export function useBook(id: number) {
  return useQuery({
    queryKey: ["/books", id],
    queryFn: async () => {
      const response = await bookService.get(id);
      return response.data.data;
    },
    enabled: !!id,
  });
}

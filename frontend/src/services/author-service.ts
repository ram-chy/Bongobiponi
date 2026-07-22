import apiClient from "@/lib/axios";
import type { Author } from "@/types/author";

export const authorService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/authors", { params }),

  get: (id: number) => apiClient.get<{ data: Author }>(`/authors/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/authors", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/authors/${id}`, data),

  delete: (id: number) => apiClient.delete(`/authors/${id}`),
};

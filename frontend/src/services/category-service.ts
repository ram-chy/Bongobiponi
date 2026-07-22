import apiClient from "@/lib/axios";
import type { Category } from "@/types/category";

export const categoryService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/categories", { params }),

  listAll: () =>
    apiClient.get<{ data: Category[] }>("/categories", {
      params: { per_page: 1000 },
    }),

  get: (id: number) =>
    apiClient.get<{ data: Category }>(`/categories/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/categories", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/categories/${id}`, data),

  delete: (id: number) => apiClient.delete(`/categories/${id}`),
};

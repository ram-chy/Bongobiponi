import apiClient from "@/lib/axios";
import type { Publisher } from "@/types/publisher";

export const publisherService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/publishers", { params }),

  get: (id: number) => apiClient.get<{ data: Publisher }>(`/publishers/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/publishers", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/publishers/${id}`, data),

  delete: (id: number) => apiClient.delete(`/publishers/${id}`),
};

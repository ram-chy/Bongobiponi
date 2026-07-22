import apiClient from "@/lib/axios";
import type { Purchase } from "@/types/purchase";

export const purchaseService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/purchases", { params }),

  get: (id: number) =>
    apiClient.get<{ data: Purchase }>(`/purchases/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/purchases", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/purchases/${id}`, data),

  delete: (id: number) => apiClient.delete(`/purchases/${id}`),

  confirm: (id: number) =>
    apiClient.post(`/purchases/${id}/confirm`),

  cancel: (id: number) =>
    apiClient.post(`/purchases/${id}/cancel`),
};

import apiClient from "@/lib/axios";
import type { Customer } from "@/types/customer";

export const customerService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/customers", { params }),

  get: (id: number) => apiClient.get<{ data: Customer }>(`/customers/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/customers", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/customers/${id}`, data),

  delete: (id: number) => apiClient.delete(`/customers/${id}`),
};

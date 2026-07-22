import apiClient from "@/lib/axios";
import type { Supplier } from "@/types/supplier";

export const supplierService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/suppliers", { params }),

  get: (id: number) => apiClient.get<{ data: Supplier }>(`/suppliers/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/suppliers", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/suppliers/${id}`, data),

  delete: (id: number) => apiClient.delete(`/suppliers/${id}`),
};

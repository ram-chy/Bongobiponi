import apiClient from "@/lib/axios";
import type { ReceiveOrder } from "@/types/receive-order";

export const receiveOrderService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/receive-orders", { params }),

  get: (id: number) =>
    apiClient.get<{ data: ReceiveOrder }>(`/receive-orders/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/receive-orders", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/receive-orders/${id}`, data),

  delete: (id: number) => apiClient.delete(`/receive-orders/${id}`),

  approve: (id: number) =>
    apiClient.post(`/receive-orders/${id}/approve`),

  receive: (id: number, items: { id: number; received_quantity: number }[]) =>
    apiClient.post(`/receive-orders/${id}/receive`, { items }),

  cancel: (id: number) =>
    apiClient.post(`/receive-orders/${id}/cancel`),
};

import apiClient from "@/lib/axios";
import type {
  Order,
  OrderAvailability,
  OrderComment,
  OrderFormData,
  OrderReservation,
  OrderStatus,
  OrderStatusHistory,
  OrderStatusTransitionResponse,
} from "@/types/order";

export const orderService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/orders", { params }),

  get: (id: number) =>
    apiClient.get<{ data: Order }>(`/orders/${id}`),

  create: (data: OrderFormData) =>
    apiClient.post("/orders", data),

  update: (id: number, data: OrderFormData) =>
    apiClient.put(`/orders/${id}`, data),

  delete: (id: number) =>
    apiClient.delete(`/orders/${id}`),

  getAvailability: (id: number) =>
    apiClient.get<{ data: OrderAvailability }>(`/orders/${id}/availability`),

  getStatusHistory: (id: number) =>
    apiClient.get<{ data: OrderStatusHistory[] }>(`/orders/${id}/status-history`),

  getReservations: (id: number) =>
    apiClient.get<{ data: OrderReservation[] }>(`/orders/${id}/reservations`),

  getComments: (id: number) =>
    apiClient.get<{ data: OrderComment[] }>(`/orders/${id}/comments`),

  createComment: (id: number, comment: string) =>
    apiClient.post<{ data: OrderComment }>(`/orders/${id}/comments`, {
      comment,
    }),

  updateStatus: (id: number, status: OrderStatus) =>
    apiClient.post<OrderStatusTransitionResponse>(`/orders/${id}/status`, {
      status,
    }),

  downloadPdf: (id: number) =>
    apiClient.get(`/orders/${id}/download-pdf`, {
      responseType: "blob",
    }),

  restore: (id: number) =>
    apiClient.post(`/orders/${id}/restore`),
};

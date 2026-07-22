import apiClient from "@/lib/axios";
import type { Order, OrderFormData } from "@/types/order";

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

  downloadPdf: (id: number) =>
    apiClient.get(`/orders/${id}/download-pdf`, {
      responseType: "blob",
    }),

  restore: (id: number) =>
    apiClient.post(`/orders/${id}/restore`),

  getQuotationItems: (customerId: number) =>
    apiClient.get(`/quotations`, {
      params: { customer_id: customerId, per_page: 100 },
    }),
};

import apiClient from "@/lib/axios";
import type { SalesOrder, SalesOrderFormData } from "@/types/sales-order";

export const salesOrderService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/sales-orders", { params }),

  get: (id: number) =>
    apiClient.get<{ data: SalesOrder }>(`/sales-orders/${id}`),

  create: (data: SalesOrderFormData) =>
    apiClient.post("/sales-orders", data),

  update: (id: number, data: SalesOrderFormData) =>
    apiClient.put(`/sales-orders/${id}`, data),

  delete: (id: number) =>
    apiClient.delete(`/sales-orders/${id}`),

  downloadPdf: (id: number) =>
    apiClient.get(`/sales-orders/${id}/download-pdf`, {
      responseType: "blob",
    }),

  restore: (id: number) =>
    apiClient.post(`/sales-orders/${id}/restore`),

  getOrderBookingItems: (customerId: number) =>
    apiClient.get("/orders", {
      params: { customer_id: customerId, per_page: 100 },
    }),
};

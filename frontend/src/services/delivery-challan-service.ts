import apiClient from "@/lib/axios";
import type { DeliveryChallan, DeliveryChallanFormData } from "@/types/delivery-challan";
import type { SalesOrder } from "@/types/sales-order";

export const deliveryChallanService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/delivery-challans", { params }),

  get: (id: number) =>
    apiClient.get<{ data: DeliveryChallan }>(`/delivery-challans/${id}`),

  create: (data: DeliveryChallanFormData) =>
    apiClient.post("/delivery-challans", data),

  update: (id: number, data: DeliveryChallanFormData) =>
    apiClient.put(`/delivery-challans/${id}`, data),

  delete: (id: number) =>
    apiClient.delete(`/delivery-challans/${id}`),

  downloadPdf: (id: number) =>
    apiClient.get(`/delivery-challans/${id}/download-pdf`, {
      responseType: "blob",
    }),

  getSalesOrderItems: (customerId: number) =>
    apiClient.get("/sales-orders", {
      params: { customer_id: customerId, per_page: 100 },
    }),
};

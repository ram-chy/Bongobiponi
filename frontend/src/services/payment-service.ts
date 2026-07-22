import apiClient from "@/lib/axios";
import type { DueInvoice, Payment } from "@/types/payment";

export const paymentService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/payments", { params }),

  get: (id: number) =>
    apiClient.get<{ data: Payment }>(`/payments/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/payments", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/payments/${id}`, data),

  delete: (id: number) =>
    apiClient.delete(`/payments/${id}`),

  downloadPdf: (id: number) =>
    apiClient.get(`/payments/${id}/download-pdf`, { responseType: "blob" }),

  getCustomerDueInvoices: (customerId: number) =>
    apiClient.get<{ data: DueInvoice[] }>(`/customers/${customerId}/due-invoices`),
};

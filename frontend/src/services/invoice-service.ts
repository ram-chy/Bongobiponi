import apiClient from "@/lib/axios";
import type { Invoice, InvoiceFormData, InvoiceableItem } from "@/types/invoice";

export const invoiceService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/invoices", { params }),

  get: (id: number) =>
    apiClient.get<{ data: Invoice }>(`/invoices/${id}`),

  create: (data: InvoiceFormData) =>
    apiClient.post("/invoices", data),

  update: (id: number, data: InvoiceFormData) =>
    apiClient.put(`/invoices/${id}`, data),

  delete: (id: number) =>
    apiClient.delete(`/invoices/${id}`),

  downloadPdf: (id: number) =>
    apiClient.get(`/invoices/${id}/download-pdf`, { responseType: "blob" }),

  getInvoiceableItems: (deliveryChallanId: number) =>
    apiClient.get<{ data: InvoiceableItem[] }>(`/delivery-challans/${deliveryChallanId}/invoiceable-items`),
};

import apiClient from "@/lib/axios";
import type { Quotation, QuotationFormData } from "@/types/quotation";

export const quotationService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/quotations", { params }),

  get: (id: number) =>
    apiClient.get<{ data: Quotation }>(`/quotations/${id}`),

  create: (data: QuotationFormData) =>
    apiClient.post("/quotations", data),

  update: (id: number, data: QuotationFormData) =>
    apiClient.put(`/quotations/${id}`, data),

  delete: (id: number) =>
    apiClient.delete(`/quotations/${id}`),

  downloadPdf: (id: number) =>
    apiClient.get(`/quotations/${id}/download-pdf`, {
      responseType: "blob",
    }),

  restore: (id: number) =>
    apiClient.post(`/quotations/${id}/restore`),
};

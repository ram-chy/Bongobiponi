import apiClient from "@/lib/axios";
import type { Expense, ExpenseCategory } from "@/types/expense";

export const expenseService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/expenses", { params }),

  get: (id: number) =>
    apiClient.get<{ data: Expense }>(`/expenses/${id}`),

  create: (data: FormData) =>
    apiClient.post("/expenses", data, {
      headers: { "Content-Type": "multipart/form-data" },
    }),

  update: (id: number, data: FormData) =>
    apiClient.post(`/expenses/${id}`, data, {
      headers: { "Content-Type": "multipart/form-data" },
      params: { _method: "PUT" },
    }),

  delete: (id: number) =>
    apiClient.delete(`/expenses/${id}`),

  downloadAttachment: (id: number) =>
    apiClient.get(`/expenses/${id}/download-attachment`, { responseType: "blob" }),
};

export const expenseCategoryService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/expense-categories", { params }),

  get: (id: number) =>
    apiClient.get<{ data: ExpenseCategory }>(`/expense-categories/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/expense-categories", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/expense-categories/${id}`, data),

  delete: (id: number) =>
    apiClient.delete(`/expense-categories/${id}`),
};

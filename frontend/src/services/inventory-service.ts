import apiClient from "@/lib/axios";
import type { InventoryTransaction, Stock } from "@/types/inventory";

export const inventoryService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/inventory", { params }),

  getStock: (bookId: number) =>
    apiClient.get<{ data: Stock }>(`/inventory/${bookId}`),

  getLedger: (bookId: number, params?: Record<string, unknown>) =>
    apiClient.get(`/inventory/ledger/${bookId}`, { params }),

  createOpening: (data: Record<string, unknown>) =>
    apiClient.post("/inventory/opening", data),

  createAdjustment: (data: Record<string, unknown>) =>
    apiClient.post("/inventory/adjustment", data),

  createDamage: (data: Record<string, unknown>) =>
    apiClient.post("/inventory/damage", data),
};

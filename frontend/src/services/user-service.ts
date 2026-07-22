import apiClient from "@/lib/axios";
import type { UpdateRolePayload } from "@/types/user";

export const userService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/users", { params }),

  get: (id: number) => apiClient.get(`/users/${id}`),

  updateRole: (id: number, payload: UpdateRolePayload) =>
    apiClient.put(`/users/${id}/role`, payload),
};

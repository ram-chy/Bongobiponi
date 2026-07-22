"use client";

import { useQuery } from "@tanstack/react-query";
import { userService } from "@/services/user-service";
import type { UserData } from "@/types/user";
import type { PaginatedResponse } from "@/types/entity";

export function useUsers(params?: Record<string, unknown>) {
  return useQuery<PaginatedResponse<UserData>>({
    queryKey: ["/users", params],
    queryFn: async () => {
      const response = await userService.list(params);
      return response.data;
    },
  });
}

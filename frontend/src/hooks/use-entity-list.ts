"use client";

import { useQuery, type UseQueryResult } from "@tanstack/react-query";
import apiClient from "@/lib/axios";
import type {
  PaginatedResponse,
  ListParams,
  EntityConfig,
} from "@/types/entity";

export function useEntityList<T>(
  config: EntityConfig<T>,
  params: ListParams
): UseQueryResult<PaginatedResponse<T>> {
  return useQuery({
    queryKey: [config.endpoint, params],
    queryFn: async () => {
      const response = await apiClient.get<PaginatedResponse<T>>(
        config.endpoint,
        { params }
      );
      return response.data;
    },
    placeholderData: (previousData) => previousData,
  });
}

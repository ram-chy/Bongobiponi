"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { userService } from "@/services/user-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useUpdateRole() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({
      userId,
      roleId,
    }: {
      userId: number;
      roleId: number;
    }) => userService.updateRole(userId, { role_id: roleId }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/users"] });
      toast.success("Role updated successfully");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(
          error.response?.data?.message || "Failed to update role"
        );
      } else {
        toast.error("Failed to update role");
      }
    },
  });
}

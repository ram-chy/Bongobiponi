"use client";

import { useMutation } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { authService } from "@/services/auth-service";
import { useAuthStore } from "@/store/auth-store";

export function useLogout() {
  const router = useRouter();
  const logout = useAuthStore((state) => state.logout);

  return useMutation({
    mutationFn: () => authService.logout(),
    onSettled: () => {
      logout();
      toast.success("Logged out successfully");
      router.push("/login");
    },
  });
}

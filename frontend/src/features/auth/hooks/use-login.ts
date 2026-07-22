"use client";

import { useMutation } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { authService } from "@/services/auth-service";
import { useAuthStore } from "@/store/auth-store";
import type { LoginPayload } from "@/types/auth";

export function useLogin() {
  const router = useRouter();
  const setToken = useAuthStore((state) => state.setToken);
  const setUser = useAuthStore((state) => state.setUser);

  return useMutation({
    mutationFn: (payload: LoginPayload) => authService.login(payload),
    onSuccess: async (response) => {
      const { access_token } = response.data.data;
      setToken(access_token);

      const meResponse = await authService.me();
      setUser(meResponse.data.data);

      router.push("/dashboard");
    },
  });
}

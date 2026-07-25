"use client";

import { useMutation } from "@tanstack/react-query";
import { authService } from "@/services/auth-service";
import type { VerifyOtpPayload } from "@/types/auth";

export function useVerifyOtp() {
  return useMutation({
    mutationFn: async (payload: VerifyOtpPayload) => {
      const response = await authService.verifyOtp(payload);
      const token = response.data.data?.reset_token;
      return { token };
    },
  });
}

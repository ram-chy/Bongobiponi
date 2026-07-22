import apiClient from "@/lib/axios";
import type { AuthResponse, ForgotPasswordPayload, LoginPayload, MeResponse, RegisterPayload, ResetPasswordPayload, VerifyOtpPayload } from "@/types/auth";

export const authService = {
  login: (payload: LoginPayload) =>
    apiClient.post<AuthResponse>("/login", payload),

  register: (payload: RegisterPayload) =>
    apiClient.post<AuthResponse>("/register", payload),

  me: () => apiClient.get<MeResponse>("/me"),

  logout: () => apiClient.post("/logout"),

  refresh: () => apiClient.post<AuthResponse>("/refresh"),

  forgotPassword: (payload: ForgotPasswordPayload) =>
    apiClient.post("/forgot-password", payload),

  verifyOtp: (payload: VerifyOtpPayload) =>
    apiClient.post<{ message: string }>("/verify-otp", payload),

  resetPassword: (payload: ResetPasswordPayload & { token: string }) =>
    apiClient.post("/reset-password", payload),
};

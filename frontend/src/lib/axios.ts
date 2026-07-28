import axios, { AxiosError, type InternalAxiosRequestConfig } from "axios";
import { useAuthStore } from "@/store/auth-store";

interface ApiErrorData {
  message?: string;
  errors?: Record<string, string[]>;
}

const COOKIE_NAME = "auth_token";

const apiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  withCredentials: true,
});

function getTokenFromCookie(): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(new RegExp(`(?:^|;\\s*)${COOKIE_NAME}=([^;]*)`));
  return match ? decodeURIComponent(match[1]) : null;
}

apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // For FormData, remove Content-Type so the browser sets it with the correct boundary
    if (config.data instanceof FormData && config.headers) {
      if (typeof (config.headers as Record<string, unknown>).delete === 'function') {
        (config.headers as { delete: (h: string) => void }).delete('Content-Type');
      } else {
        delete (config.headers as Record<string, unknown>)['Content-Type'];
      }
    }

    // 1. Try in-memory store first (set after login)
    let token = useAuthStore.getState().token;

    // 2. If not in memory (e.g., after page refresh), read from cookie.
    // The auth_token cookie is NOT HttpOnly so JS can read it for hydration.
    // Hydrate the store so subsequent requests use the in-memory token.
    if (!token) {
      token = getTokenFromCookie();
      if (token) {
        useAuthStore.getState().setToken(token);
      }
    }

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiErrorData>) => {
    if (!error.response) {
      return Promise.reject(error);
    }

    const { status } = error.response;

    if (status === 401) {
      useAuthStore.getState().logout();
      if (typeof window !== "undefined") {
        window.location.href = "/login";
      }
      return Promise.reject(error);
    }

    return Promise.reject(error);
  }
);

export default apiClient;
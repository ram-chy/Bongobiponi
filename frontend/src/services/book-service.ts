import apiClient from "@/lib/axios";
import type { Book } from "@/types/book";

export const bookService = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/books", { params }),

  get: (id: number) => apiClient.get<{ data: Book }>(`/books/${id}`),

  create: (data: Record<string, unknown>) =>
    apiClient.post("/books", data),

  update: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/books/${id}`, data),

  delete: (id: number) => apiClient.delete(`/books/${id}`),

  uploadCover: (file: File) => {
    const formData = new FormData();
    formData.append("cover_image", file);
    return apiClient.post<{ message: string; data: { url: string; path: string } }>(
      "/books/upload-cover",
      formData,
    );
  },
};

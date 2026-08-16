"use client";

import { useMutation } from "@tanstack/react-query";
import { bookService } from "@/services/book-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useBookExport() {
  return useMutation({
    mutationFn: () => bookService.exportXlsx(),
    onSuccess: (response) => {
      const blob = response.data;
      const contentDisposition = response.headers?.["content-disposition"];
      let filename = "book-catalogue.xlsx";
      if (contentDisposition) {
        const match = contentDisposition.match(/filename="?([^"]+)"?/);
        if (match) filename = match[1];
      }
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      toast.success("Catalogue downloaded");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(
          error.response?.data?.message || "Failed to download catalogue"
        );
      } else {
        toast.error("Failed to download catalogue");
      }
    },
  });
}

"use client";

import { useMutation } from "@tanstack/react-query";
import { deliveryChallanService } from "@/services/delivery-challan-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function useDeliveryChallanDownload() {
  return useMutation({
    mutationFn: async (id: number) => {
      const response = await deliveryChallanService.downloadPdf(id);
      return response;
    },
    onSuccess: (response) => {
      const blob = response.data;
      const contentDisposition = response.headers?.["content-disposition"];
      let filename = "delivery-challan.pdf";
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
      toast.success("PDF download started");
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Failed to download PDF");
      } else {
        toast.error("Failed to download PDF");
      }
    },
  });
}

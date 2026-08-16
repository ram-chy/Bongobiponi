"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { AxiosError } from "axios";
import { toast } from "sonner";
import { orderService } from "@/services/order-service";
import type { OrderStatus } from "@/types/order";

interface TransitionVariables {
  id: number;
  status: OrderStatus;
}

export function useOrderStatusTransition() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, status }: TransitionVariables) => {
      const response = await orderService.updateStatus(id, status);
      return response.data;
    },
    onSuccess: (response) => {
      const id = response.data.id;
      const status = response.data.status;
      queryClient.invalidateQueries({ queryKey: ["/orders"] });
      queryClient.invalidateQueries({ queryKey: ["/orders", id] });
      queryClient.invalidateQueries({ queryKey: ["/orders", id, "availability"] });
      queryClient.invalidateQueries({ queryKey: ["/orders", id, "status-history"] });
      queryClient.invalidateQueries({ queryKey: ["/orders", id, "reservations"] });
      if (status === "cancelled") {
        toast.success("Order cancelled successfully. Reserved stock has been released.");
      } else if (status === "rto") {
        toast.success("Order marked as RTO. The shipment has been returned to origin.");
      } else {
        toast.success(response.message || "Order status updated successfully.");
      }
    },
    onError: (error) => {
      if (error instanceof AxiosError) {
        toast.error(error.response?.data?.message || "Failed to update order status.");
      } else {
        toast.error("Failed to update order status.");
      }
      queryClient.invalidateQueries({ queryKey: ["/orders"] });
    },
  });
}

"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { orderService } from "@/services/order-service";
import { toast } from "sonner";

export function useOrderComments(orderId: number) {
  const queryClient = useQueryClient();

  const commentsQuery = useQuery({
    queryKey: ["/orders", orderId, "comments"],
    queryFn: async () => {
      const response = await orderService.getComments(orderId);
      return response.data.data;
    },
  });

  const addComment = useMutation({
    mutationFn: (comment: string) => orderService.createComment(orderId, comment),
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: ["/orders", orderId, "comments"],
      });
      toast.success("Comment added");
    },
    onError: () => {
      toast.error("Failed to add comment");
    },
  });

  return { commentsQuery, addComment };
}

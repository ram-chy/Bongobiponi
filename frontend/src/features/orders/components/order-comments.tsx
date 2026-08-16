"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import { MessageSquarePlus, Loader2 } from "lucide-react";
import { useOrderComments } from "@/features/orders/hooks/use-order-comments";
import type { OrderComment } from "@/types/order";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDateTime(value: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${pad(date.getDate())} ${months[date.getMonth()]} ${date.getFullYear()}, ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

interface OrderCommentsProps {
  orderId: number;
}

export function OrderComments({ orderId }: OrderCommentsProps) {
  const { commentsQuery, addComment } = useOrderComments(orderId);
  const [draft, setDraft] = useState("");

  const comments: OrderComment[] = commentsQuery.data ?? [];

  const handleSubmit = () => {
    const text = draft.trim();
    if (!text || addComment.isPending) return;
    addComment.mutate(text, {
      onSuccess: () => setDraft(""),
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Comments</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-2">
          <Textarea
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
            placeholder="Add a free-form comment for this order..."
            rows={3}
          />
          <div className="flex justify-end">
            <Button
              type="button"
              size="sm"
              onClick={handleSubmit}
              disabled={!draft.trim() || addComment.isPending}
            >
              {addComment.isPending ? (
                <Loader2 className="size-4 animate-spin" />
              ) : (
                <MessageSquarePlus className="size-4" />
              )}
              Add Comment
            </Button>
          </div>
        </div>

        {commentsQuery.isLoading ? (
          <div className="flex items-center justify-center py-6">
            <Loader2 className="size-5 animate-spin text-muted-foreground" />
          </div>
        ) : comments.length === 0 ? (
          <p className="py-2 text-center text-sm text-muted-foreground">
            No comments yet.
          </p>
        ) : (
          <ul className="space-y-3">
            {comments.map((comment) => (
              <li
                key={comment.id}
                className="rounded-lg border p-3 text-sm"
              >
                <div className="mb-1 flex items-center justify-between gap-2">
                  <span className="font-medium">
                    {comment.user?.name ?? "Unknown"}
                  </span>
                  <span className="text-xs text-muted-foreground">
                    {fmtDateTime(comment.created_at)}
                  </span>
                </div>
                <p className="whitespace-pre-wrap text-muted-foreground">
                  {comment.comment}
                </p>
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  );
}

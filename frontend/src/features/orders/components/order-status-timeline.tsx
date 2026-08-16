"use client";

import { AlertTriangle, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { OrderStatusBadge } from "@/features/orders/components/order-status-badge";
import { ORDER_STATUS_META } from "@/features/orders/order-status-meta";
import { useOrderStatusHistory } from "@/features/orders/hooks/use-order-status-history";
import type { OrderStatus } from "@/types/order";

const MONTHS = [
  "Jan", "Feb", "Mar", "Apr", "May", "Jun",
  "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
];

function formatTimestamp(iso: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;

  const day = date.getDate();
  const month = MONTHS[date.getMonth()];
  const year = date.getFullYear();

  let hours = date.getHours();
  const minutes = date.getMinutes().toString().padStart(2, "0");
  const meridiem = hours >= 12 ? "PM" : "AM";
  hours = hours % 12 || 12;

  return `${day} ${month} ${year}, ${hours}:${minutes} ${meridiem}`;
}

interface OrderStatusTimelineProps {
  id: number;
  status: OrderStatus | null | undefined;
}

export function OrderStatusTimeline({ id, status }: OrderStatusTimelineProps) {
  const { data: history, isLoading, isError, refetch, isFetching } = useOrderStatusHistory(id);

  let content: React.ReactNode;

  if (isLoading) {
    content = (
      <p className="flex items-center gap-2 text-sm text-muted-foreground">
        <Loader2 className="size-4 animate-spin" />
        Loading order history...
      </p>
    );
  } else if (isError) {
    content = (
      <div className="flex items-center justify-between gap-4 py-2">
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
          <AlertTriangle className="size-4" />
          Unable to load status history.
        </div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => refetch()}
          disabled={isFetching}
        >
          Retry
        </Button>
      </div>
    );
  } else if (!history || history.length === 0) {
    content = (
      <p className="py-2 text-sm text-muted-foreground">
        No status history is available for this Order.
      </p>
    );
  } else {
    content = (
      <ol className="relative space-y-6">
        {history.map((entry, index) => (
          <li key={entry.id} className="relative pl-8">
            {index < history.length - 1 && (
              <span
                aria-hidden
                className="absolute left-[5px] top-5 bottom-[-22px] w-px bg-border"
              />
            )}
            <span
              aria-hidden
              className="absolute left-0 top-1.5 size-[11px] rounded-full border-2 border-background bg-muted-foreground"
            />
            <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
              <OrderStatusBadge status={entry.to_status} />
              {entry.from_status && (
                <span className="text-xs text-muted-foreground">
                  from {ORDER_STATUS_META[entry.from_status].label}
                </span>
              )}
            </div>
            <p className="mt-1 text-sm">{entry.reason || "Status changed"}</p>
            <p className="text-xs text-muted-foreground">
              {entry.changed_by ? entry.changed_by.name : "System"}
              <span aria-hidden className="mx-1.5">·</span>
              {formatTimestamp(entry.created_at)}
            </p>
          </li>
        ))}
      </ol>
    );
  }

  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between space-y-0">
        <CardTitle>Fulfillment Timeline</CardTitle>
        {status && (
          <div className="flex items-center gap-2">
            <span className="text-sm text-muted-foreground">Current Status</span>
            <OrderStatusBadge status={status} />
          </div>
        )}
      </CardHeader>
      <CardContent>{content}</CardContent>
    </Card>
  );
}

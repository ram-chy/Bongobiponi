"use client";

import { AlertTriangle, CheckCircle2, Loader2, RefreshCw } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { getAvailabilitySummary } from "@/features/orders/order-status-meta";
import { useOrderAvailability } from "@/features/orders/hooks/use-order-availability";
import { cn } from "@/lib/utils";

const toneStyles = {
  success: "bg-emerald-600 text-white hover:bg-emerald-600/80",
  warning: "bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400",
  danger: "bg-destructive/10 text-destructive",
} as const;

export function OrderAvailability({ id }: { id: number }) {
  const { data, isLoading, isError, refetch, isFetching } = useOrderAvailability(id);

  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between space-y-0">
        <CardTitle>Order Availability</CardTitle>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => refetch()}
          disabled={isFetching}
        >
          <RefreshCw className={cn("size-4", isFetching && "animate-spin")} />
          Refresh Availability
        </Button>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="flex items-center gap-2 py-8 text-sm text-muted-foreground">
            <Loader2 className="size-4 animate-spin" />
            Checking availability...
          </div>
        ) : isError ? (
          <div className="flex items-center justify-between gap-4 py-8">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <AlertTriangle className="size-4" />
              Availability could not be loaded.
            </div>
            <Button type="button" variant="outline" size="sm" onClick={() => refetch()}>
              Retry
            </Button>
          </div>
        ) : data && data.items.length === 0 ? (
          <p className="py-8 text-sm text-muted-foreground">No items to evaluate.</p>
        ) : data ? (
          <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-3">
              <Badge
                variant="default"
                className={toneStyles[getAvailabilitySummary(data.status).tone]}
              >
                {getAvailabilitySummary(data.status).label}
              </Badge>
              <span className="text-sm text-muted-foreground">
                {getAvailabilitySummary(data.status).detail}
              </span>
            </div>

            <div className="overflow-x-auto rounded-lg border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Book</TableHead>
                    <TableHead className="text-right">Required</TableHead>
                    <TableHead className="text-right">Available</TableHead>
                    <TableHead className="text-right">Shortage</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.items.map((item, index) => (
                    <TableRow key={item.book_id ?? `missing-${index}`}>
                      <TableCell className="font-medium">
                        {item.book_title || "Unknown Book"}
                      </TableCell>
                      <TableCell className="text-right">{item.required_quantity}</TableCell>
                      <TableCell className="text-right">{item.available_quantity}</TableCell>
                      <TableCell className="text-right">
                        {item.unverifiable ? "-" : item.shortage_quantity}
                      </TableCell>
                      <TableCell>
                        {item.unverifiable ? (
                          <span className="inline-flex items-center gap-1 text-sm text-amber-600 dark:text-amber-400">
                            <AlertTriangle className="size-3.5" />
                            Cannot verify
                          </span>
                        ) : item.is_available ? (
                          <span className="inline-flex items-center gap-1 text-sm text-emerald-600 dark:text-emerald-400">
                            <CheckCircle2 className="size-3.5" />
                            Available
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 text-sm text-destructive">
                            <AlertTriangle className="size-3.5" />
                            Short
                          </span>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>
        ) : null}
      </CardContent>
    </Card>
  );
}

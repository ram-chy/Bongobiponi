"use client";

import Link from "next/link";
import { ArrowLeft, Pencil, CheckCircle, XCircle, Package } from "lucide-react";
import { Button, buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useReceiveOrder } from "@/features/receive-orders/hooks/use-receive-order";
import { receiveOrderService } from "@/services/receive-order-service";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { cn } from "@/lib/utils";
import { useState } from "react";

const months = [
  "Jan", "Feb", "Mar", "Apr", "May", "Jun",
  "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

const statusLabels: Record<string, string> = {
  draft: "Draft",
  approved: "Approved",
  partially_received: "Partially Received",
  completed: "Completed",
  cancelled: "Cancelled",
};

const statusClasses: Record<string, string> = {
  draft: "",
  approved: "bg-blue-600 hover:bg-blue-600/80",
  partially_received: "bg-amber-500 hover:bg-amber-500/80",
  completed: "bg-emerald-600 hover:bg-emerald-600/80",
  cancelled: "",
};

export function ViewReceiveOrderPage({ id }: { id: number }) {
  const { data: receiveOrder, isLoading } = useReceiveOrder(id);
  const queryClient = useQueryClient();
  const [actionLoading, setActionLoading] = useState<string | null>(null);

  const handleAction = async (
    action: "approve" | "cancel",
    fn: () => Promise<unknown>
  ) => {
    setActionLoading(action);
    try {
      await fn();
      queryClient.invalidateQueries({ queryKey: ["/receive-orders"] });
      queryClient.invalidateQueries({ queryKey: ["/receive-orders", id] });
      toast.success(
        action === "approve"
          ? "Receive order approved"
          : "Receive order cancelled"
      );
    } catch (error: unknown) {
      const msg =
        error instanceof Error ? error.message : "Action failed";
      toast.error(msg);
    } finally {
      setActionLoading(null);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-32 text-muted-foreground">
        Loading...
      </div>
    );
  }

  if (!receiveOrder) {
    return (
      <div className="flex items-center justify-center h-32 text-muted-foreground">
        Receive order not found.
      </div>
    );
  }

  const isDraft = receiveOrder.status === "draft";
  const isApprovedOrPartial =
    receiveOrder.status === "approved" ||
    receiveOrder.status === "partially_received";

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Receive Orders", href: "/receive-orders" },
          { label: receiveOrder.order_no },
        ]}
      />
      <PageHeader
        title={receiveOrder.order_no}
        description="Receive order details"
        actions={
          <div className="flex items-center gap-2">
            {isDraft && (
              <>
                <Link
                  href={`/receive-orders/${id}/edit`}
                  className={cn(
                    buttonVariants({ variant: "outline" }),
                    "gap-1.5"
                  )}
                >
                  <Pencil className="size-4" />
                  Edit
                </Link>
                <Button
                  variant="default"
                  className="gap-1.5 bg-blue-600 hover:bg-blue-600/80"
                  disabled={actionLoading === "approve"}
                  onClick={() =>
                    handleAction("approve", () =>
                      receiveOrderService.approve(id)
                    )
                  }
                >
                  <CheckCircle className="size-4" />
                  Approve
                </Button>
                <Button
                  variant="destructive"
                  className="gap-1.5"
                  disabled={actionLoading === "cancel"}
                  onClick={() =>
                    handleAction("cancel", () =>
                      receiveOrderService.cancel(id)
                    )
                  }
                >
                  <XCircle className="size-4" />
                  Cancel
                </Button>
              </>
            )}
            {isApprovedOrPartial && (
              <Button
                variant="destructive"
                className="gap-1.5"
                disabled={actionLoading === "cancel"}
                onClick={() =>
                  handleAction("cancel", () =>
                    receiveOrderService.cancel(id)
                  )
                }
              >
                <XCircle className="size-4" />
                Cancel
              </Button>
            )}
          </div>
        }
      />

      <div className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Order Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Order No</p>
                <p className="font-medium">{receiveOrder.order_no}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Supplier</p>
                <p className="font-medium">
                  {receiveOrder.supplier?.company_name ?? "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Order Placer (Customer)</p>
                <p className="font-medium">
                  {receiveOrder.customer?.company_name ?? receiveOrder.customer?.name ?? "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">
                  Expected Delivery Date
                </p>
                <p className="font-medium">
                  {fmtDate(receiveOrder.expected_delivery_date)}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Reference No</p>
                <p className="font-medium">
                  {receiveOrder.reference_no || "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Status</p>
                <Badge
                  variant={
                    receiveOrder.status === "completed"
                      ? "default"
                      : receiveOrder.status === "cancelled"
                        ? "destructive"
                        : "secondary"
                  }
                  className={statusClasses[receiveOrder.status]}
                >
                  {statusLabels[receiveOrder.status] ?? receiveOrder.status}
                </Badge>
              </div>
              {receiveOrder.notes && (
                <div className="sm:col-span-2">
                  <p className="text-sm text-muted-foreground">Notes</p>
                  <p className="font-medium">{receiveOrder.notes}</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Items</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Book</TableHead>
                    <TableHead className="text-right">Ordered Qty</TableHead>
                    <TableHead className="text-right">Received Qty</TableHead>
                    <TableHead className="text-right">Price</TableHead>
                    <TableHead>Remarks</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {receiveOrder.items?.map((item) => (
                    <TableRow key={item.id}>
                      <TableCell>{item.book?.title ?? "-"}</TableCell>
                      <TableCell className="text-right">
                        {item.ordered_quantity}
                      </TableCell>
                      <TableCell className="text-right">
                        {item.received_quantity}
                      </TableCell>
                      <TableCell className="text-right">
                        {Number(item.purchase_price).toFixed(2)}
                      </TableCell>
                      <TableCell>{item.remarks || "-"}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>System Info</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Created By</p>
                <p className="font-medium">
                  {receiveOrder.created_by
                    ? `${receiveOrder.created_by.first_name} ${receiveOrder.created_by.last_name}`
                    : "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Created At</p>
                <p className="font-medium">
                  {fmtDate(receiveOrder.created_at)}
                </p>
              </div>
              {receiveOrder.updated_by && (
                <>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated By</p>
                    <p className="font-medium">
                      {receiveOrder.updated_by.first_name}{" "}
                      {receiveOrder.updated_by.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated At</p>
                    <p className="font-medium">
                      {fmtDate(receiveOrder.updated_at)}
                    </p>
                  </div>
                </>
              )}
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => window.history.back()}
          >
            <ArrowLeft className="size-4" />
            Back
          </Button>
        </div>
      </div>
    </div>
  );
}

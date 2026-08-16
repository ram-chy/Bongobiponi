"use client";

import Link from "next/link";
import { ArrowLeft, Pencil, CheckCircle, XCircle } from "lucide-react";
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
import { usePurchase } from "@/features/purchases/hooks/use-purchase";
import { purchaseService } from "@/services/purchase-service";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { cn } from "@/lib/utils";
import { useState } from "react";
import { ConfirmDialog } from "@/components/dialogs/confirm-dialog";

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
  confirmed: "Confirmed",
  cancelled: "Cancelled",
};

const statusClasses: Record<string, string> = {
  draft: "",
  confirmed: "bg-blue-600 hover:bg-blue-600/80",
  cancelled: "",
};

const purchaseTypeLabels: Record<string, string> = {
  manual: "Manual",
};

export function ViewPurchasePage({ id, fromOrderId }: { id: number; fromOrderId?: number }) {
  const { data: purchase, isLoading } = usePurchase(id);
  const queryClient = useQueryClient();
  const [actionLoading, setActionLoading] = useState<string | null>(null);
  const [cancelDialogOpen, setCancelDialogOpen] = useState(false);
  const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);

  const handleAction = async (
    action: "confirm" | "cancel",
    fn: () => Promise<unknown>
  ) => {
    setActionLoading(action);
    try {
      await fn();
      queryClient.invalidateQueries({ queryKey: ["/purchases"] });
      queryClient.invalidateQueries({ queryKey: ["/purchases", id] });
      if (fromOrderId) {
        queryClient.invalidateQueries({ queryKey: ["/orders", fromOrderId] });
        queryClient.invalidateQueries({ queryKey: ["/orders", fromOrderId, "availability"] });
        queryClient.invalidateQueries({ queryKey: ["/orders", fromOrderId, "status-history"] });
      }
      toast.success(
        action === "confirm"
          ? "Purchase confirmed"
          : "Purchase cancelled"
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

  if (!purchase) {
    return (
      <div className="flex items-center justify-center h-32 text-muted-foreground">
        Purchase not found.
      </div>
    );
  }

  const isDraft = purchase.status === "draft";

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Purchases", href: "/purchases" },
          { label: purchase.purchase_no },
        ]}
      />
      <PageHeader
        title={purchase.purchase_no}
        description="Purchase details"
        actions={
          <div className="flex items-center gap-2">
            {fromOrderId && (
              <Link
                href={`/orders/${fromOrderId}`}
                className={cn(buttonVariants({ variant: "outline" }), "gap-1.5")}
              >
                <ArrowLeft className="size-4" />
                Return to Order
              </Link>
            )}
            {isDraft && (
              <>
                <Link
                  href={`/purchases/${id}/edit`}
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
                  disabled={actionLoading === "confirm"}
                  onClick={() => setConfirmDialogOpen(true)}
                >
                  <CheckCircle className="size-4" />
                  Confirm
                </Button>
                <Button
                  variant="destructive"
                  className="gap-1.5"
                  disabled={actionLoading === "cancel"}
                  onClick={() => setCancelDialogOpen(true)}
                >
                  <XCircle className="size-4" />
                  Cancel
                </Button>
              </>
            )}
          </div>
        }
      />

      <div className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Purchase Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Purchase No</p>
                <p className="font-medium">{purchase.purchase_no}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Supplier</p>
                <p className="font-medium">
                  {purchase.supplier?.company_name ?? "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Publisher</p>
                <p className="font-medium">
                  {purchase.publisher?.name ?? "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Purchase Type</p>
                <Badge variant="outline">
                  {purchaseTypeLabels[purchase.purchase_type] ?? purchase.purchase_type}
                </Badge>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Invoice No</p>
                <p className="font-medium">{purchase.invoice_no || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Invoice Date</p>
                <p className="font-medium">{fmtDate(purchase.invoice_date)}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Purchase Date</p>
                <p className="font-medium">{fmtDate(purchase.purchase_date)}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Status</p>
                <Badge
                  variant={
                    purchase.status === "confirmed"
                      ? "default"
                      : purchase.status === "cancelled"
                        ? "destructive"
                        : "outline"
                  }
                  className={statusClasses[purchase.status]}
                >
                  {statusLabels[purchase.status] ?? purchase.status}
                </Badge>
              </div>
              {purchase.notes && (
                <div className="sm:col-span-2">
                  <p className="text-sm text-muted-foreground">Notes</p>
                  <p className="font-medium">{purchase.notes}</p>
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
                    <TableHead className="text-right">Printed Price</TableHead>
                    <TableHead className="text-right">Discount %</TableHead>
                    <TableHead className="text-right">Total</TableHead>
                    <TableHead>Remarks</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {purchase.items?.map((item) => (
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
                      <TableCell className="text-right">
                        {item.printed_price !== null && item.printed_price !== undefined
                          ? Number(item.printed_price).toFixed(2)
                          : "-"}
                      </TableCell>
                      <TableCell className="text-right">
                        {item.discount_percentage !== null && item.discount_percentage !== undefined
                          ? `${Number(item.discount_percentage).toFixed(2)}%`
                          : "-"}
                      </TableCell>
                      <TableCell className="text-right font-medium">
                        {Number(item.total).toFixed(2)}
                      </TableCell>
                      <TableCell>{item.remarks || "-"}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            <div className="mt-4 space-y-1 border-t pt-4 text-right text-sm">
              <div className="flex justify-between gap-4 border-t pt-1">
                <span className="font-semibold">Grand Total</span>
                <span className="font-semibold">
                  {new Intl.NumberFormat("en-IN", {
                    style: "currency",
                    currency: "INR",
                  }).format(purchase.total_amount ?? 0)}
                </span>
              </div>
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
                  {purchase.created_by
                    ? `${purchase.created_by.first_name} ${purchase.created_by.last_name}`
                    : "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Created At</p>
                <p className="font-medium">
                  {fmtDate(purchase.created_at)}
                </p>
              </div>
              {purchase.updated_by && (
                <>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated By</p>
                    <p className="font-medium">
                      {purchase.updated_by.first_name}{" "}
                      {purchase.updated_by.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated At</p>
                    <p className="font-medium">
                      {fmtDate(purchase.updated_at)}
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

      <ConfirmDialog
        open={confirmDialogOpen}
        onOpenChange={setConfirmDialogOpen}
        title="Confirm Purchase"
        description="This will add the purchased items to inventory stock. This action cannot be undone."
        confirmLabel="Confirm Purchase"
        onConfirm={() => {
          setConfirmDialogOpen(false);
          handleAction("confirm", () => purchaseService.confirm(id));
        }}
        isLoading={actionLoading === "confirm"}
        variant="default"
      />

      <ConfirmDialog
        open={cancelDialogOpen}
        onOpenChange={setCancelDialogOpen}
        title="Cancel Purchase"
        description="This will cancel the purchase. If the purchase was confirmed, inventory will be reversed. This action cannot be undone."
        confirmLabel="Cancel Purchase"
        onConfirm={() => {
          setCancelDialogOpen(false);
          handleAction("cancel", () => purchaseService.cancel(id));
        }}
        isLoading={actionLoading === "cancel"}
        variant="destructive"
      />
    </div>
  );
}

"use client";

import { useRouter } from "next/navigation";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { ArrowLeft, Pencil, Download, Loader2 } from "lucide-react";
import { useSalesOrder } from "@/features/sales-orders/hooks/use-sales-order";
import { useSalesOrderDownload } from "@/features/sales-orders/hooks/use-sales-order-download";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

const statusVariants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
  draft: "default",
  confirmed: "secondary",
  cancelled: "destructive",
  completed: "default",
};

interface ViewSalesOrderPageProps {
  id: number;
}

export function ViewSalesOrderPage({ id }: ViewSalesOrderPageProps) {
  const router = useRouter();
  const { data: salesOrder, isLoading } = useSalesOrder(id);
  const downloadMutation = useSalesOrderDownload();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!salesOrder) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Sales Order not found.
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Sales Orders", href: "/sales-orders" },
          { label: salesOrder.sales_order_serial },
        ]}
      />
      <PageHeader
        title={salesOrder.sales_order_serial}
        actions={
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              onClick={() => downloadMutation.mutate(id)}
              disabled={downloadMutation.isPending}
            >
              <Download className="size-4" />
              Download PDF
            </Button>
            <Button onClick={() => router.push(`/sales-orders/${id}/edit`)}>
              <Pencil className="size-4" />
              Edit
            </Button>
          </div>
        }
      />

      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Customer Information</CardTitle>
          </CardHeader>
          <CardContent>
            {salesOrder.customer ? (
              <div className="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                  <span className="text-muted-foreground">Company: </span>
                  <span className="font-medium">
                    {salesOrder.customer.company_name || "-"}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Contact: </span>
                  <span className="font-medium">{salesOrder.customer.name}</span>
                </div>
                <div>
                  <span className="text-muted-foreground">Address: </span>
                  <span className="font-medium">
                    {salesOrder.customer.billing_address}, {salesOrder.customer.city}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Mobile: </span>
                  <span className="font-medium">{salesOrder.customer.phone}</span>
                </div>
                {salesOrder.customer.email && (
                  <div>
                    <span className="text-muted-foreground">Email: </span>
                    <span className="font-medium">
                      {salesOrder.customer.email}
                    </span>
                  </div>
                )}
                {salesOrder.customer.gst_number && (
                  <div>
                    <span className="text-muted-foreground">GST: </span>
                    <span className="font-medium">
                      {salesOrder.customer.gst_number}
                    </span>
                  </div>
                )}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">No customer data</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Sales Order Details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
            <div>
              <span className="text-muted-foreground">Sales Order Date: </span>
              <span className="font-medium">{fmtDate(salesOrder.sales_order_date)}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Expected Delivery: </span>
              <span className="font-medium">{fmtDate(salesOrder.expected_delivery_date)}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Source: </span>
              <span className="font-medium capitalize">{salesOrder.sales_order_source}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Status: </span>
              <Badge
                variant={statusVariants[salesOrder.status] ?? "default"}
                className={
                  salesOrder.status === "completed"
                    ? "bg-emerald-600 hover:bg-emerald-600/80"
                    : undefined
                }
              >
                {salesOrder.status.toUpperCase()}
              </Badge>
            </div>
            {salesOrder.reference_notes && (
              <div className="sm:col-span-2">
                <span className="text-muted-foreground">Reference: </span>
                <span className="font-medium">{salesOrder.reference_notes}</span>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Items</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto rounded-lg border">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b bg-muted/50">
                    <th className="p-2 text-left font-medium">#</th>
                    <th className="p-2 text-left font-medium">Source</th>
                    <th className="p-2 text-left font-medium">Order</th>
                    <th className="p-2 text-left font-medium">Description</th>
                    <th className="p-2 text-left font-medium">Unit</th>
                    <th className="p-2 text-right font-medium">Order Qty</th>
                    <th className="p-2 text-right font-medium">SO Qty</th>
                    <th className="p-2 text-right font-medium">Rate</th>
                    <th className="p-2 text-right font-medium">Amount</th>
                    <th className="p-2 text-left font-medium">Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  {salesOrder.items.map((item, index) => (
                    <tr key={item.id ?? index} className="border-b last:border-0">
                      <td className="p-2 text-muted-foreground">
                        {item.item_no}
                      </td>
                      <td className="p-2">
                        <span className="text-xs font-medium uppercase text-muted-foreground">
                          {item.source_type}
                        </span>
                      </td>
                      <td className="p-2 text-xs text-muted-foreground">
                        #{item.order_id}
                      </td>
                      <td className="p-2 font-medium">{item.description}</td>
                      <td className="p-2">{item.unit}</td>
                      <td className="p-2 text-right">{item.ordered_quantity}</td>
                      <td className="p-2 text-right font-medium">
                        {item.sales_order_quantity}
                      </td>
                      <td className="p-2 text-right">{item.unit_price}</td>
                      <td className="p-2 text-right font-medium">
                        {item.line_total}
                      </td>
                      <td className="p-2 text-muted-foreground">
                        {item.remarks || "-"}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="ml-auto mt-4 w-full max-w-xs space-y-1 rounded-lg border p-4">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Subtotal</span>
                <span>{salesOrder.subtotal}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Discount</span>
                <span>{salesOrder.discount_amount}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Tax</span>
                <span>{salesOrder.tax_amount}</span>
              </div>
              <div className="flex justify-between border-t pt-1 font-medium">
                <span>Grand Total</span>
                <span>{salesOrder.grand_total}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        {salesOrder.notes && (
          <Card>
            <CardHeader>
              <CardTitle>Notes</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="whitespace-pre-wrap text-sm">{salesOrder.notes}</p>
            </CardContent>
          </Card>
        )}

        <div className="pb-8">
          <Button
            type="button"
            variant="ghost"
            onClick={() => router.push("/sales-orders")}
          >
            <ArrowLeft className="size-4" />
            Back to Sales Orders
          </Button>
        </div>
      </div>
    </div>
  );
}

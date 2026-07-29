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
import { useDeliveryChallan } from "@/features/delivery-challans/hooks/use-delivery-challan";
import { useDeliveryChallanDownload } from "@/features/delivery-challans/hooks/use-delivery-challan-download";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

const statusVariants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
  draft: "default",
  ready: "secondary",
  dispatched: "default",
  delivered: "default",
  cancelled: "destructive",
};

interface ViewDeliveryChallanPageProps {
  id: number;
}

export function ViewDeliveryChallanPage({ id }: ViewDeliveryChallanPageProps) {
  const router = useRouter();
  const { data: dc, isLoading } = useDeliveryChallan(id);
  const downloadMutation = useDeliveryChallanDownload();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!dc) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Delivery Challan not found.
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Delivery Challans", href: "/delivery-challans" },
          { label: dc.delivery_challan_serial },
        ]}
      />
      <PageHeader
        title={dc.delivery_challan_serial}
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
            <Button onClick={() => router.push(`/delivery-challans/${id}/edit`)}>
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
            {dc.customer ? (
              <div className="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                  <span className="text-muted-foreground">Company: </span>
                  <span className="font-medium">
                    {dc.customer.company_name || "-"}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Contact: </span>
                  <span className="font-medium">{dc.customer.name}</span>
                </div>
                <div>
                  <span className="text-muted-foreground">Address: </span>
                  <span className="font-medium">
                    {dc.customer.billing_address}, {dc.customer.city}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Mobile: </span>
                  <span className="font-medium">{dc.customer.phone}</span>
                </div>
                {dc.customer.email && (
                  <div>
                    <span className="text-muted-foreground">Email: </span>
                    <span className="font-medium">{dc.customer.email}</span>
                  </div>
                )}
                {dc.customer.gst_number && (
                  <div>
                    <span className="text-muted-foreground">GST: </span>
                    <span className="font-medium">{dc.customer.gst_number}</span>
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
            <CardTitle>Delivery Details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
            <div>
              <span className="text-muted-foreground">Delivery Date: </span>
              <span className="font-medium">{fmtDate(dc.delivery_date)}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Status: </span>
              <Badge
                variant={statusVariants[dc.status] ?? "default"}
                className={
                  dc.status === "delivered"
                    ? "bg-emerald-600 hover:bg-emerald-600/80"
                    : dc.status === "dispatched"
                      ? "bg-blue-600 hover:bg-blue-600/80"
                      : undefined
                }
              >
                {dc.status.toUpperCase()}
              </Badge>
            </div>
            <div className="sm:col-span-2">
              <span className="text-muted-foreground">Delivery Address: </span>
              <span className="font-medium">{dc.delivery_address}</span>
            </div>
            {dc.transport_name && (
              <div>
                <span className="text-muted-foreground">Transport: </span>
                <span className="font-medium">{dc.transport_name}</span>
              </div>
            )}
            {dc.vehicle_number && (
              <div>
                <span className="text-muted-foreground">Vehicle: </span>
                <span className="font-medium">{dc.vehicle_number}</span>
              </div>
            )}
            {dc.driver_name && (
              <div>
                <span className="text-muted-foreground">Driver: </span>
                <span className="font-medium">{dc.driver_name}</span>
              </div>
            )}
            {dc.lr_number && (
              <div>
                <span className="text-muted-foreground">LR No: </span>
                <span className="font-medium">{dc.lr_number}</span>
              </div>
            )}
            {dc.status === "delivered" && dc.delivery_by && (
              <div>
                <span className="text-muted-foreground">Delivered By: </span>
                <span className="font-medium">{dc.delivery_by}</span>
              </div>
            )}
            {dc.status === "delivered" && dc.receiver_name && (
              <div>
                <span className="text-muted-foreground">Received By: </span>
                <span className="font-medium">{dc.receiver_name}</span>
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
                    <th className="p-2 text-left font-medium">Order Serial</th>
                    <th className="p-2 text-left font-medium">Description</th>
                    <th className="p-2 text-left font-medium">Unit</th>
                    <th className="p-2 text-right font-medium">Order Qty</th>
                    <th className="p-2 text-right font-medium">Delivery Qty</th>
                    <th className="p-2 text-left font-medium">Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  {dc.items.map((item, index) => (
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
                        {item.order_serial ?? '-'}
                      </td>
                      <td className="p-2 font-medium">{item.description}</td>
                      <td className="p-2">{item.unit}</td>
                      <td className="p-2 text-right">{item.ordered_quantity}</td>
                      <td className="p-2 text-right font-medium">
                        {item.delivery_quantity}
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
                <span>{dc.subtotal}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Discount</span>
                <span>{dc.discount_amount}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Tax</span>
                <span>{dc.tax_amount}</span>
              </div>
              <div className="flex justify-between border-t pt-1 font-medium">
                <span>Grand Total</span>
                <span>{dc.grand_total}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        {dc.remarks && (
          <Card>
            <CardHeader>
              <CardTitle>Remarks</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="whitespace-pre-wrap text-sm">{dc.remarks}</p>
            </CardContent>
          </Card>
        )}

        <div className="pb-8">
          <Button
            type="button"
            variant="ghost"
            onClick={() => router.push("/delivery-challans")}
          >
            <ArrowLeft className="size-4" />
            Back to Delivery Challans
          </Button>
        </div>
      </div>
    </div>
  );
}

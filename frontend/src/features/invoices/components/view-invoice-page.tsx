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
import { useInvoice } from "@/features/invoices/hooks/use-invoice";
import { useInvoiceDownload } from "@/features/invoices/hooks/use-invoice-download";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

const statusVariants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
  draft: "default",
  issued: "default",
  partially_paid: "default",
  paid: "default",
  cancelled: "destructive",
};

interface ViewInvoicePageProps {
  id: number;
}

export function ViewInvoicePage({ id }: ViewInvoicePageProps) {
  const router = useRouter();
  const { data: invoice, isLoading } = useInvoice(id);
  const downloadMutation = useInvoiceDownload();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!invoice) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Invoice not found.
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Invoices", href: "/invoices" },
          { label: invoice.serial },
        ]}
      />
      <PageHeader
        title={invoice.serial}
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
            <Button onClick={() => router.push(`/invoices/${id}/edit`)}>
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
            {invoice.customer ? (
              <div className="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                  <span className="text-muted-foreground">Company: </span>
                  <span className="font-medium">
                    {invoice.customer.company_name || "-"}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Contact: </span>
                  <span className="font-medium">{invoice.customer.name}</span>
                </div>
                <div>
                  <span className="text-muted-foreground">Address: </span>
                  <span className="font-medium">
                    {invoice.customer.billing_address}, {invoice.customer.city}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Mobile: </span>
                  <span className="font-medium">{invoice.customer.phone}</span>
                </div>
                {invoice.customer.email && (
                  <div>
                    <span className="text-muted-foreground">Email: </span>
                    <span className="font-medium">{invoice.customer.email}</span>
                  </div>
                )}
                {invoice.customer.gst_number && (
                  <div>
                    <span className="text-muted-foreground">GST: </span>
                    <span className="font-medium">{invoice.customer.gst_number}</span>
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
            <CardTitle>Invoice Details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
            <div>
              <span className="text-muted-foreground">Invoice Date: </span>
              <span className="font-medium">{fmtDate(invoice.invoice_date)}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Due Date: </span>
              <span className="font-medium">{fmtDate(invoice.due_date)}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Status: </span>
              <Badge
                variant={statusVariants[invoice.status] ?? "default"}
                className={
                  invoice.status === "paid"
                    ? "bg-emerald-600 hover:bg-emerald-600/80"
                    : invoice.status === "issued"
                      ? "bg-blue-600 hover:bg-blue-600/80"
                      : invoice.status === "partially_paid"
                        ? "bg-amber-600 hover:bg-amber-600/80"
                        : undefined
                }
              >
                {invoice.status.replace("_", " ").toUpperCase()}
              </Badge>
            </div>
            <div className="sm:col-span-2">
              <span className="text-muted-foreground">Billing Address: </span>
              <span className="font-medium">{invoice.billing_address}</span>
            </div>
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
                    <th className="p-2 text-left font-medium">DC Serial</th>
                    <th className="p-2 text-left font-medium">SO Serial</th>
                    <th className="p-2 text-left font-medium">Description</th>
                    <th className="p-2 text-left font-medium">Unit</th>
                    <th className="p-2 text-right font-medium">Delivered Qty</th>
                    <th className="p-2 text-right font-medium">Invoice Qty</th>
                    <th className="p-2 text-right font-medium">Unit Price</th>
                    <th className="p-2 text-right font-medium">Line Total</th>
                  </tr>
                </thead>
                <tbody>
                  {invoice.items.map((item, index) => (
                    <tr key={item.id ?? index} className="border-b last:border-0">
                      <td className="p-2 text-muted-foreground">{index + 1}</td>
                      <td className="p-2 text-xs text-muted-foreground">
                        {item.delivery_challan_serial ?? `#${item.delivery_challan_id}`}
                      </td>
                      <td className="p-2 text-xs text-muted-foreground">
                        {item.sales_order_serial ?? `#${item.sales_order_id}`}
                      </td>
                      <td className="p-2 font-medium">{item.item_description}</td>
                      <td className="p-2">{item.unit}</td>
                      <td className="p-2 text-right">{item.delivered_quantity}</td>
                      <td className="p-2 text-right font-medium">{item.invoiced_quantity}</td>
                      <td className="p-2 text-right">{item.unit_price}</td>
                      <td className="p-2 text-right font-medium">{item.line_total}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="ml-auto mt-4 w-full max-w-xs space-y-1 rounded-lg border p-4">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Subtotal</span>
                <span>{invoice.subtotal}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Discount</span>
                <span>{invoice.discount_amount}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Tax</span>
                <span>{invoice.tax_amount}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Round Off</span>
                <span>{invoice.round_off}</span>
              </div>
              <div className="flex justify-between border-t pt-1 font-medium">
                <span>Grand Total</span>
                <span>{invoice.grand_total}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        {invoice.remarks && (
          <Card>
            <CardHeader>
              <CardTitle>Remarks</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="whitespace-pre-wrap text-sm">{invoice.remarks}</p>
            </CardContent>
          </Card>
        )}

        <div className="pb-8">
          <Button
            type="button"
            variant="ghost"
            onClick={() => router.push("/invoices")}
          >
            <ArrowLeft className="size-4" />
            Back to Invoices
          </Button>
        </div>
      </div>
    </div>
  );
}

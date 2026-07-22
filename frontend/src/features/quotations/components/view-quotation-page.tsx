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
import { useQuotation } from "@/features/quotations/hooks/use-quotation";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const [y, m, day] = d.split("-");
  return `${parseInt(day)} ${months[parseInt(m) - 1]} ${y}`;
}
import { useQuotationDownload } from "@/features/quotations/hooks/use-quotation-download";

interface ViewQuotationPageProps {
  id: number;
}

const statusVariants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
  draft: "default",
  sent: "secondary",
  accepted: "default",
  rejected: "destructive",
  expired: "outline",
};

export function ViewQuotationPage({ id }: ViewQuotationPageProps) {
  const router = useRouter();
  const { data: quotation, isLoading } = useQuotation(id);
  const downloadMutation = useQuotationDownload();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!quotation) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Quotation not found.
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Quotations", href: "/quotations" },
          { label: quotation.quotation_serial },
        ]}
      />
      <PageHeader
        title={quotation.quotation_serial}
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
            <Button onClick={() => router.push(`/quotations/${id}/edit`)}>
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
            {quotation.customer ? (
              <div className="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                  <span className="text-muted-foreground">Company: </span>
                  <span className="font-medium">
                    {quotation.customer.company_name || "-"}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Contact: </span>
                  <span className="font-medium">{quotation.customer.name}</span>
                </div>
                <div>
                  <span className="text-muted-foreground">Address: </span>
                  <span className="font-medium">
                    {quotation.customer.billing_address}, {quotation.customer.city}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Mobile: </span>
                  <span className="font-medium">{quotation.customer.phone}</span>
                </div>
                {quotation.customer.email && (
                  <div>
                    <span className="text-muted-foreground">Email: </span>
                    <span className="font-medium">
                      {quotation.customer.email}
                    </span>
                  </div>
                )}
                {quotation.customer.gst_number && (
                  <div>
                    <span className="text-muted-foreground">GST: </span>
                    <span className="font-medium">
                      {quotation.customer.gst_number}
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
            <CardTitle>Quotation Details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
            <div>
              <span className="text-muted-foreground">Quotation Date: </span>
              <span className="font-medium">{fmtDate(quotation.quotation_date)}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Valid Until: </span>
              <span className="font-medium">{fmtDate(quotation.valid_until)}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Status: </span>
              <Badge
                variant={statusVariants[quotation.status] ?? "default"}
                className={
                  quotation.status === "accepted"
                    ? "bg-emerald-600 hover:bg-emerald-600/80"
                    : undefined
                }
              >
                {quotation.status.toUpperCase()}
              </Badge>
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
                    <th className="p-2 text-left font-medium">Description</th>
                    <th className="p-2 text-left font-medium">Unit</th>
                    <th className="p-2 text-right font-medium">Qty</th>
                    <th className="p-2 text-right font-medium">Rate</th>
                    <th className="p-2 text-right font-medium">Disc %</th>
                    <th className="p-2 text-right font-medium">Tax %</th>
                    <th className="p-2 text-right font-medium">Amount</th>
                    <th className="p-2 text-left font-medium">Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  {quotation.items.map((item, index) => (
                    <tr key={item.id ?? index} className="border-b last:border-0">
                      <td className="p-2 text-muted-foreground">
                        {item.item_no}
                      </td>
                      <td className="p-2 font-medium">{item.description}</td>
                      <td className="p-2">{item.unit}</td>
                      <td className="p-2 text-right">{item.quantity}</td>
                      <td className="p-2 text-right">{item.unit_price}</td>
                      <td className="p-2 text-right">
                        {parseFloat(item.discount_percentage) > 0
                          ? `${item.discount_percentage}%`
                          : "-"}
                      </td>
                      <td className="p-2 text-right">
                        {parseFloat(item.tax_percentage) > 0
                          ? `${item.tax_percentage}%`
                          : "-"}
                      </td>
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
                <span>{quotation.subtotal}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Discount</span>
                <span>{quotation.discount_amount}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Tax</span>
                <span>{quotation.tax_amount}</span>
              </div>
              <div className="flex justify-between border-t pt-1 font-medium">
                <span>Grand Total</span>
                <span>{quotation.grand_total}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        {quotation.notes && (
          <Card>
            <CardHeader>
              <CardTitle>Notes</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="whitespace-pre-wrap text-sm">{quotation.notes}</p>
            </CardContent>
          </Card>
        )}

        <div className="pb-8">
          <Button
            type="button"
            variant="ghost"
            onClick={() => router.push("/quotations")}
          >
            <ArrowLeft className="size-4" />
            Back to Quotations
          </Button>
        </div>
      </div>
    </div>
  );
}

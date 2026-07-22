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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { ArrowLeft, Pencil, Download, Loader2 } from "lucide-react";
import { usePayment } from "@/features/payments/hooks/use-payment";
import { usePaymentDownload } from "@/features/payments/hooks/use-payment-download";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

interface ViewPaymentPageProps {
  id: number;
}

export function ViewPaymentPage({ id }: ViewPaymentPageProps) {
  const router = useRouter();
  const { data: payment, isLoading } = usePayment(id);
  const downloadMutation = usePaymentDownload();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!payment) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Payment not found.
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Payments", href: "/payments" },
          { label: payment.payment_no },
        ]}
      />
      <PageHeader
        title={payment.payment_no}
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
            <Button onClick={() => router.push(`/payments/${id}/edit`)}>
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
            {payment.customer ? (
              <div className="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                  <span className="text-muted-foreground">Company: </span>
                  <span className="font-medium">
                    {payment.customer.company_name || "-"}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Contact: </span>
                  <span className="font-medium">{payment.customer.name}</span>
                </div>
                <div>
                  <span className="text-muted-foreground">Mobile: </span>
                  <span className="font-medium">{payment.customer.phone}</span>
                </div>
                {payment.customer.email && (
                  <div>
                    <span className="text-muted-foreground">Email: </span>
                    <span className="font-medium">{payment.customer.email}</span>
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
            <CardTitle>Payment Details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
            <div>
              <span className="text-muted-foreground">Payment Date: </span>
              <span className="font-medium">{fmtDate(payment.payment_date)}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Payment Method: </span>
              <Badge variant="outline">{payment.payment_method}</Badge>
            </div>
            <div>
              <span className="text-muted-foreground">Reference No: </span>
              <span className="font-medium">{payment.reference_no || "-"}</span>
            </div>
            <div>
              <span className="text-muted-foreground">Total Amount: </span>
              <span className="font-medium">{parseFloat(payment.total_amount).toFixed(2)}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Invoices Covered</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto rounded-lg border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Invoice No</TableHead>
                    <TableHead className="text-right">Invoice Amount</TableHead>
                    <TableHead className="text-right">Paid Amount</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {payment.items.map((item) => (
                    <TableRow key={item.id}>
                      <TableCell className="font-medium">
                        {item.invoice?.serial ?? "-"}
                      </TableCell>
                      <TableCell className="text-right">
                        {parseFloat(item.invoice?.grand_total ?? "0").toFixed(2)}
                      </TableCell>
                      <TableCell className="text-right font-medium">
                        {parseFloat(item.paid_amount).toFixed(2)}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>

            <div className="ml-auto mt-4 w-full max-w-xs space-y-1 rounded-lg border p-4">
              <div className="flex justify-between border-t pt-1 font-medium">
                <span>Total Received</span>
                <span>{parseFloat(payment.total_amount).toFixed(2)}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        {payment.remarks && (
          <Card>
            <CardHeader>
              <CardTitle>Remarks</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="whitespace-pre-wrap text-sm">{payment.remarks}</p>
            </CardContent>
          </Card>
        )}

        <div className="pb-8">
          <Button
            type="button"
            variant="ghost"
            onClick={() => router.push("/payments")}
          >
            <ArrowLeft className="size-4" />
            Back to Payments
          </Button>
        </div>
      </div>
    </div>
  );
}

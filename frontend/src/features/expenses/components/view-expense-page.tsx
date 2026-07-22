"use client";

import Link from "next/link";
import { ArrowLeft, Download, Pencil } from "lucide-react";
import { Button, buttonVariants } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import { useExpense } from "@/features/expenses/hooks/use-expense";
import { useExpenseDelete } from "@/features/expenses/hooks/use-expense-delete";
import { expenseService } from "@/services/expense-service";
import { cn } from "@/lib/utils";
import { toast } from "sonner";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

const methodVariants: Record<string, "default" | "secondary" | "outline"> = {
  Cash: "default",
  "Bank Transfer": "secondary",
  UPI: "outline",
  Cheque: "outline",
};

export function ViewExpensePage({ id }: { id: number }) {
  const { data: expense, isLoading } = useExpense(id);
  const deleteMutation = useExpenseDelete();

  const handleDownload = async () => {
    if (!expense?.attachment) return;
    try {
      const response = await expenseService.downloadAttachment(expense.id);
      const blob = response.data instanceof Blob ? response.data : new Blob([response.data]);
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute("download", `${expense.expense_no.replace(/\//g, "_")}.jpg`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      console.error("Download failed:", err);
      toast.error("Failed to download attachment");
    }
  };

  if (isLoading) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Loading...</div>;
  }

  if (!expense) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Expense not found.</div>;
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Accounts" },
          { label: "Expenses", href: "/expenses" },
          { label: expense.expense_no },
        ]}
      />
      <PageHeader
        title={expense.expense_no}
        description={`Expense details`}
        actions={
          <div className="flex items-center gap-2">
            <Link
              href={`/expenses/${id}/edit`}
              className={cn(buttonVariants({ variant: "outline" }), "gap-1.5")}
            >
              <Pencil className="size-4" />
              Edit
            </Link>
          </div>
        }
      />

      <div className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Expense Details</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Date</p>
                <p className="font-medium">{fmtDate(expense.expense_date)}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Category</p>
                <p className="font-medium">{expense.category?.name ?? "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Vendor</p>
                <p className="font-medium">{expense.vendor_name || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Amount</p>
                <p className="font-medium">{parseFloat(expense.amount).toFixed(2)}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Payment Details</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Payment Method</p>
                <Badge variant={methodVariants[expense.payment_method] ?? "outline"}>
                  {expense.payment_method}
                </Badge>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Reference No</p>
                <p className="font-medium">{expense.reference_no || "-"}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {expense.attachment && (
          <Card>
            <CardHeader>
              <CardTitle>Attachment</CardTitle>
            </CardHeader>
            <CardContent>
              <Button variant="outline" size="sm" onClick={handleDownload} className="gap-1.5">
                <Download className="size-4" />
                Download Attachment
              </Button>
            </CardContent>
          </Card>
        )}

        {expense.remarks && (
          <Card>
            <CardHeader>
              <CardTitle>Remarks</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm">{expense.remarks}</p>
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle>System Info</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Created By</p>
                <p className="font-medium">
                  {expense.created_by
                    ? `${expense.created_by.first_name} ${expense.created_by.last_name}`
                    : "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Created At</p>
                <p className="font-medium">{fmtDate(expense.created_at)}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => window.history.back()}
          >
            Back
          </Button>
        </div>
      </div>
    </div>
  );
}

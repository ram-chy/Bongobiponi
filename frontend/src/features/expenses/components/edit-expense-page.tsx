"use client";

import { useMemo } from "react";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { ExpenseForm } from "@/features/expenses/components/expense-form";
import { useExpense } from "@/features/expenses/hooks/use-expense";

export function EditExpensePage({ id }: { id: number }) {
  const { data: expense, isLoading } = useExpense(id);

  const defaultValues = useMemo(() => {
    if (!expense) return undefined;
    return {
      expense_date: expense.expense_date?.split("T")[0] ?? "",
      category_id: expense.category ? String(expense.category.id) : "",
      payment_method: expense.payment_method,
      reference_no: expense.reference_no ?? "",
      vendor_name: expense.vendor_name,
      amount: expense.amount,
      remarks: expense.remarks ?? "",
      attachment: expense.attachment,
    };
  }, [expense]);

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
          { label: expense.expense_no, href: `/expenses/${id}` },
          { label: "Edit" },
        ]}
      />
      <PageHeader title={`Edit ${expense.expense_no}`} />
      <ExpenseForm id={id} defaultValues={defaultValues} />
    </div>
  );
}

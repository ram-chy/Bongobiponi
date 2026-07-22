import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { ExpenseForm } from "@/features/expenses/components/expense-form";

export function CreateExpensePage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Accounts" },
          { label: "Expenses", href: "/expenses" },
          { label: "Create Expense" },
        ]}
      />
      <PageHeader title="Create Expense" />
      <ExpenseForm />
    </div>
  );
}

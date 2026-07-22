"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { SalesOrderForm } from "@/features/sales-orders/components/sales-order-form";

export function CreateSalesOrderPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Sales Orders", href: "/sales-orders" },
          { label: "Create Sales Order" },
        ]}
      />
      <PageHeader title="Create Sales Order" />
      <SalesOrderForm />
    </div>
  );
}

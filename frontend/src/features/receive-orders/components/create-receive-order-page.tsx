"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { ReceiveOrderForm } from "@/features/receive-orders/components/receive-order-form";

export function CreateReceiveOrderPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Receive Orders", href: "/receive-orders" },
          { label: "Create Receive Order" },
        ]}
      />
      <PageHeader title="Create Receive Order" />
      <ReceiveOrderForm />
    </div>
  );
}

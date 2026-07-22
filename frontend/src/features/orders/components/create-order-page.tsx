"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { OrderForm } from "@/features/orders/components/order-form";

export function CreateOrderPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Order Booking", href: "/orders" },
          { label: "Create Order" },
        ]}
      />
      <PageHeader title="Create Order" />
      <OrderForm />
    </div>
  );
}

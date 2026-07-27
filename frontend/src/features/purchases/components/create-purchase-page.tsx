"use client";

import { PurchaseForm } from "@/features/purchases/components/purchase-form";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";

export function CreatePurchasePage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Purchases" },
          { label: "Purchases", href: "/purchases" },
          { label: "Create" },
        ]}
      />
      <PageHeader
        title="Create Purchase"
        description="Create a new purchase record."
      />
      <PurchaseForm />
    </div>
  );
}

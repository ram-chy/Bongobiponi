"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { DeliveryChallanForm } from "@/features/delivery-challans/components/delivery-challan-form";

export function CreateDeliveryChallanPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Delivery Challans", href: "/delivery-challans" },
          { label: "Create Delivery Challan" },
        ]}
      />
      <PageHeader title="Create Delivery Challan" />
      <DeliveryChallanForm />
    </div>
  );
}

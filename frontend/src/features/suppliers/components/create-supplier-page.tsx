"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { SupplierForm } from "@/features/suppliers/components/supplier-form";

export function CreateSupplierPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Suppliers", href: "/suppliers" },
          { label: "Create Supplier" },
        ]}
      />
      <PageHeader title="Create Supplier" />
      <SupplierForm />
    </div>
  );
}

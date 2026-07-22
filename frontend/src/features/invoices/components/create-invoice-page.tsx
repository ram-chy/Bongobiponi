"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { InvoiceForm } from "@/features/invoices/components/invoice-form";

export function CreateInvoicePage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Invoices", href: "/invoices" },
          { label: "Create Invoice" },
        ]}
      />
      <PageHeader title="Create Invoice" />
      <InvoiceForm />
    </div>
  );
}

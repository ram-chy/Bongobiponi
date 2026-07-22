"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { QuotationForm } from "@/features/quotations/components/quotation-form";

export function CreateQuotationPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Quotations", href: "/quotations" },
          { label: "Create Quotation" },
        ]}
      />
      <PageHeader title="Create Quotation" />
      <QuotationForm />
    </div>
  );
}

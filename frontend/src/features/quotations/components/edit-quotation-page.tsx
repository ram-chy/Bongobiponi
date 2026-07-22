"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { QuotationForm } from "@/features/quotations/components/quotation-form";
import { useQuotation } from "@/features/quotations/hooks/use-quotation";
import { Loader2 } from "lucide-react";

interface EditQuotationPageProps {
  id: number;
}

export function EditQuotationPage({ id }: EditQuotationPageProps) {
  const { data: quotation, isLoading } = useQuotation(id);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Quotations", href: "/quotations" },
          { label: "Edit Quotation" },
        ]}
      />
      <PageHeader title="Edit Quotation" />
      <QuotationForm
        id={id}
        customer={quotation?.customer ?? null}
        defaultValues={
          quotation
            ? {
                customer_id: String(quotation.customer?.id ?? ""),
                quotation_date: String(quotation.quotation_date ?? "").slice(0, 10),
                valid_until: String(quotation.valid_until ?? "").slice(0, 10),
                status: quotation.status,
                notes: quotation.notes ?? "",
                items: quotation.items.map((item) => ({
                  description: item.description,
                  quantity: String(item.quantity ?? ""),
                  unit: item.unit,
                  unit_price: String(item.unit_price ?? ""),
                  discount_percentage: String(item.discount_percentage ?? ""),
                  tax_percentage: String(item.tax_percentage ?? ""),
                  remarks: item.remarks ?? "",
                })),
              }
            : undefined
        }
      />
    </div>
  );
}

"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { InvoiceForm } from "@/features/invoices/components/invoice-form";
import { useInvoice } from "@/features/invoices/hooks/use-invoice";
import { Loader2 } from "lucide-react";

interface EditInvoicePageProps {
  id: number;
}

export function EditInvoicePage({ id }: EditInvoicePageProps) {
  const { data: invoice, isLoading } = useInvoice(id);

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
          { label: "Invoices", href: "/invoices" },
          { label: "Edit Invoice" },
        ]}
      />
      <PageHeader title="Edit Invoice" />
      <InvoiceForm
        id={id}
        defaultValues={
          invoice
            ? {
                customer_id: String(invoice.customer?.id ?? ""),
                invoice_date: String(invoice.invoice_date ?? "").slice(0, 10),
                due_date: String(invoice.due_date ?? "").slice(0, 10),
                billing_address: invoice.billing_address ?? "",
                status: invoice.status === "issued" ? "issued" : "draft",
                remarks: invoice.remarks ?? "",
                items: invoice.items.map((item) => ({
                  delivery_challan_item_id: String(item.delivery_challan_item_id),
                  delivery_challan_id: String(item.delivery_challan_id),
                  delivery_challan_serial: item.delivery_challan_serial,
                  item_description: item.item_description,
                  unit: item.unit,
                  delivered_quantity: String(item.delivered_quantity ?? ""),
                  already_invoiced: String(
                    parseFloat(item.invoiced_quantity) + parseFloat(item.remaining_invoice_quantity)
                  ),
                  available_for_invoicing: String(
                    parseFloat(item.delivered_quantity) - parseFloat(item.invoiced_quantity)
                  ),
                  invoiced_quantity: String(item.invoiced_quantity ?? ""),
                  unit_price: String(item.unit_price ?? ""),
                  remarks: item.remarks ?? "",
                })),
              }
            : undefined
        }
      />
    </div>
  );
}

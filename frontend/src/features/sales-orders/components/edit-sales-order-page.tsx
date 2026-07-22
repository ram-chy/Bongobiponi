"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { SalesOrderForm } from "@/features/sales-orders/components/sales-order-form";
import { useSalesOrder } from "@/features/sales-orders/hooks/use-sales-order";
import { Loader2 } from "lucide-react";

interface EditSalesOrderPageProps {
  id: number;
}

export function EditSalesOrderPage({ id }: EditSalesOrderPageProps) {
  const { data: salesOrder, isLoading } = useSalesOrder(id);

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
          { label: "Sales Orders", href: "/sales-orders" },
          { label: "Edit Sales Order" },
        ]}
      />
      <PageHeader title="Edit Sales Order" />
      <SalesOrderForm
        id={id}
        customer={salesOrder?.customer ?? null}
        defaultValues={
          salesOrder
            ? {
                customer_id: String(salesOrder.customer?.id ?? ""),
                sales_order_date: String(salesOrder.sales_order_date ?? "").slice(0, 10),
                expected_delivery_date: salesOrder.expected_delivery_date
                  ? String(salesOrder.expected_delivery_date).slice(0, 10)
                  : "",
                reference_notes: salesOrder.reference_notes ?? "",
                notes: salesOrder.notes ?? "",
                items: salesOrder.items.map((item) => ({
                  order_item_id: String(item.order_item_id),
                  order_id: String(item.order_id),
                  source_type: item.source_type,
                  description: item.description,
                  unit: item.unit,
                  ordered_quantity: String(item.ordered_quantity ?? ""),
                  sales_order_quantity: String(item.sales_order_quantity ?? ""),
                  remaining_order_quantity: String(item.remaining_sales_quantity ?? ""),
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

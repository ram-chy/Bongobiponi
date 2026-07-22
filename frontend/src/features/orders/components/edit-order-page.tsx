"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { OrderForm } from "@/features/orders/components/order-form";
import { useOrder } from "@/features/orders/hooks/use-order";
import { Loader2 } from "lucide-react";

interface EditOrderPageProps {
  id: number;
}

export function EditOrderPage({ id }: EditOrderPageProps) {
  const { data: order, isLoading } = useOrder(id);

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
          { label: "Order Booking", href: "/orders" },
          { label: "Edit Order" },
        ]}
      />
      <PageHeader title="Edit Order" />
      <OrderForm
        id={id}
        customer={order?.customer ?? null}
        defaultValues={
          order
            ? {
                customer_id: String(order.customer?.id ?? ""),
                order_date: String(order.order_date ?? "").slice(0, 10),
                expected_delivery_date: order.expected_delivery_date
                  ? String(order.expected_delivery_date).slice(0, 10)
                  : "",
                reference_notes: order.reference_notes ?? "",
                notes: order.notes ?? "",
                items: order.items.map((item) => ({
                  quotation_item_id: item.quotation_item_id
                    ? String(item.quotation_item_id)
                    : undefined,
                  quotation_id: item.quotation_id
                    ? String(item.quotation_id)
                    : undefined,
                  source_type: item.source_type,
                  description: item.description,
                  unit: item.unit,
                  ordered_quantity: String(item.ordered_quantity ?? ""),
                  unit_price: String(item.unit_price ?? ""),
                  discount_percentage: String(item.discount_percentage ?? ""),
                  tax_percentage: String(item.tax_percentage ?? ""),
                  remarks: item.remarks ?? "",
                  quoted_quantity: item.quoted_quantity
                    ? String(item.quoted_quantity)
                    : undefined,
                })),
              }
            : undefined
        }
      />
    </div>
  );
}

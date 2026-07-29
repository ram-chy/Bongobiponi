"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { DeliveryChallanForm } from "@/features/delivery-challans/components/delivery-challan-form";
import { useDeliveryChallan } from "@/features/delivery-challans/hooks/use-delivery-challan";
import { Loader2 } from "lucide-react";

interface EditDeliveryChallanPageProps {
  id: number;
}

export function EditDeliveryChallanPage({ id }: EditDeliveryChallanPageProps) {
  const { data: dc, isLoading } = useDeliveryChallan(id);

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
          { label: "Delivery Challans", href: "/delivery-challans" },
          { label: "Edit Delivery Challan" },
        ]}
      />
      <PageHeader title="Edit Delivery Challan" />
      <DeliveryChallanForm
        id={id}
        customer={dc?.customer ?? null}
        defaultValues={
          dc
            ? {
                customer_id: String(dc.customer?.id ?? ""),
                delivery_date: String(dc.delivery_date ?? "").slice(0, 10),
                delivery_address: dc.delivery_address ?? "",
                transport_name: dc.transport_name ?? "",
                vehicle_number: dc.vehicle_number ?? "",
                driver_name: dc.driver_name ?? "",
                driver_mobile: dc.driver_mobile ?? "",
                lr_number: dc.lr_number ?? "",
                delivery_by: dc.delivery_by ?? "",
                receiver_name: dc.receiver_name ?? "",
                status: (dc.status as "draft" | "ready" | "dispatched" | "delivered" | "cancelled") ?? "draft",
                remarks: dc.remarks ?? "",
                items: dc.items.map((item) => ({
                  order_booking_item_id: item.order_item_id ? String(item.order_item_id) : "",
                  order_booking_id: item.order_id ? String(item.order_id) : "",
                  source_type: item.source_type,
                  description: item.description,
                  unit: item.unit,
                  ordered_quantity: String(item.ordered_quantity ?? ""),
                  delivery_quantity: String(item.delivery_quantity ?? ""),
                  unit_price: String(item.unit_price ?? ""),
                  remarks: item.remarks ?? "",
                  order_serial: item.order_serial ?? "",
                })),
              }
            : undefined
        }
      />
    </div>
  );
}

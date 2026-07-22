"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { ReceiveOrderForm } from "@/features/receive-orders/components/receive-order-form";
import { useReceiveOrder } from "@/features/receive-orders/hooks/use-receive-order";
import { Loader2 } from "lucide-react";

export function EditReceiveOrderPage({ id }: { id: number }) {
  const { data: receiveOrder, isLoading } = useReceiveOrder(id);

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
          { label: "Receive Orders", href: "/receive-orders" },
          { label: "Edit Receive Order" },
        ]}
      />
      <PageHeader title="Edit Receive Order" />
      <ReceiveOrderForm id={id} defaultValues={receiveOrder} />
    </div>
  );
}

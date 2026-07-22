"use client";

import { PurchaseForm } from "@/features/purchases/components/purchase-form";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { usePurchase } from "@/features/purchases/hooks/use-purchase";
import { Loader2 } from "lucide-react";

export function EditPurchasePage({ id }: { id: number }) {
  const { data: purchase, isLoading } = usePurchase(id);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!purchase) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Purchase not found.
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Purchases", href: "/purchases" },
          { label: purchase.purchase_no },
          { label: "Edit" },
        ]}
      />
      <PageHeader
        title={`Edit ${purchase.purchase_no}`}
        description="Update purchase details."
      />
      <PurchaseForm defaultValues={purchase} id={id} />
    </div>
  );
}

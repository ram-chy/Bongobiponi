"use client";

import { AlertTriangle, Loader2, ShoppingCart } from "lucide-react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { OrderAvailability, OrderStatus } from "@/types/order";

interface OrderProcurementProps {
  orderId: number;
  status: OrderStatus | null | undefined;
  availability?: OrderAvailability;
  availabilityLoading?: boolean;
}

export function OrderProcurement({
  orderId,
  status,
  availability,
  availabilityLoading,
}: OrderProcurementProps) {
  const router = useRouter();

  if (status !== "to_procure") {
    return null;
  }

  const shortageItems = (availability?.items ?? []).filter(
    (item) => !item.unverifiable && item.book_id != null && item.shortage_quantity > 0
  );
  const unverifiableCount = (availability?.items ?? []).filter(
    (item) => item.unverifiable
  ).length;

  let content: React.ReactNode;

  if (availabilityLoading) {
    content = (
      <p className="flex items-center gap-2 text-sm text-muted-foreground">
        <Loader2 className="size-4 animate-spin" />
        Checking availability...
      </p>
    );
  } else if (!availability) {
    content = (
      <p className="text-sm text-muted-foreground">
        Availability has not been verified. Refresh the Availability card to enable procurement.
      </p>
    );
  } else if (availability.fully_available) {
    content = (
      <p className="text-sm text-emerald-600 dark:text-emerald-400">
        All required items are currently available.
      </p>
    );
  } else if (shortageItems.length === 0) {
    content = (
      <p className="text-sm text-muted-foreground">
        No procurable shortage found.
      </p>
    );
  } else {
    content = (
      <div className="space-y-3">
        <p className="text-sm text-muted-foreground">
          {shortageItems.length} book{shortageItems.length > 1 ? "s" : ""} require
          procurement to fulfill this order.
        </p>
        {unverifiableCount > 0 && (
          <p className="flex items-center gap-1.5 text-sm text-amber-600 dark:text-amber-400">
            <AlertTriangle className="size-3.5" />
            Cannot verify {unverifiableCount} item{unverifiableCount > 1 ? "s" : ""}.
            These will not be included.
          </p>
        )}
        <Button
          type="button"
          onClick={() => router.push(`/purchases/create?order_id=${orderId}`)}
        >
          <ShoppingCart className="size-4" />
          Procure Required Items
        </Button>
      </div>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Procurement</CardTitle>
      </CardHeader>
      <CardContent>{content}</CardContent>
    </Card>
  );
}

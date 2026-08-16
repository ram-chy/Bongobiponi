"use client";

import { useState } from "react";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { ConfirmDialog } from "@/components/dialogs/confirm-dialog";
import {
  getOrderStatusActions,
  type OrderStatusActionMeta,
} from "@/features/orders/order-status-meta";
import { useOrderStatusTransition } from "@/features/orders/hooks/use-order-status-transition";
import type { OrderAvailabilityStatus, OrderStatus } from "@/types/order";

interface OrderStatusActionsProps {
  id: number;
  status: OrderStatus | null | undefined;
  availabilityStatus?: OrderAvailabilityStatus;
}

export function OrderStatusActions({ id, status, availabilityStatus }: OrderStatusActionsProps) {
  const transitionMutation = useOrderStatusTransition();
  const [pendingAction, setPendingAction] = useState<OrderStatusActionMeta | null>(null);

  const actions = getOrderStatusActions(status);
  const fullyAvailable = availabilityStatus === "fully_available";

  const canTransition = (action: OrderStatusActionMeta) => {
    if (action.destructive) return true;
    if (action.to === "to_pack") return fullyAvailable;
    return true;
  };

  if (actions.length === 0) {
    return <p className="text-sm text-muted-foreground">No further actions available.</p>;
  }

  return (
    <div>
      <div className="flex flex-wrap items-center gap-2">
        {actions.map((action) => {
          const allowed = canTransition(action);
          return (
            <Button
              key={action.to}
              type="button"
              variant={action.destructive ? "destructive" : "default"}
              onClick={() => setPendingAction(action)}
              disabled={!allowed || transitionMutation.isPending}
              title={
                !allowed
                  ? "Order is not fully available."
                  : undefined
              }
            >
              {transitionMutation.isPending ? <Loader2 className="size-4 animate-spin" /> : null}
              {action.label}
            </Button>
          );
        })}
      </div>

      {!fullyAvailable && actions.some((action) => action.to === "to_pack") && (
        <p className="mt-2 text-sm text-amber-600 dark:text-amber-400">
          The order can only move to packing when it is fully available.
        </p>
      )}

      <ConfirmDialog
        open={pendingAction !== null}
        onOpenChange={(open) => {
          if (!open) setPendingAction(null);
        }}
        title={pendingAction?.confirmTitle ?? ""}
        description={pendingAction?.confirmDescription ?? ""}
        confirmLabel={pendingAction?.label ?? "Confirm"}
        variant={pendingAction?.destructive ? "destructive" : "default"}
        isLoading={transitionMutation.isPending}
        onConfirm={() => {
          if (pendingAction) {
            transitionMutation.mutate({ id, status: pendingAction.to }, {
              onSuccess: () => setPendingAction(null),
            });
          }
        }}
      />
    </div>
  );
}

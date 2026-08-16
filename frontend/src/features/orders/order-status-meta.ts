import type { OrderAvailabilityStatus, OrderStatus } from "@/types/order";

export interface OrderStatusMeta {
  value: OrderStatus;
  label: string;
  badgeClassName: string;
  description: string;
}

export const ORDER_STATUS_META: Record<OrderStatus, OrderStatusMeta> = {
  intake: {
    value: "intake",
    label: "Intake",
    badgeClassName: "bg-secondary text-secondary-foreground",
    description: "Order received.",
  },
  to_procure: {
    value: "to_procure",
    label: "To Procure",
    badgeClassName:
      "bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400",
    description: "Procurement required.",
  },
  to_pack: {
    value: "to_pack",
    label: "To Pack",
    badgeClassName: "bg-blue-600 text-white hover:bg-blue-600/80",
    description: "Ready for packing.",
  },
  packed: {
    value: "packed",
    label: "Packed",
    badgeClassName:
      "bg-violet-500/10 text-violet-600 dark:bg-violet-500/20 dark:text-violet-400",
    description: "Order packed.",
  },
  dispatched: {
    value: "dispatched",
    label: "Dispatched",
    badgeClassName:
      "bg-orange-500/10 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400",
    description: "Order dispatched.",
  },
  delivered: {
    value: "delivered",
    label: "Delivered",
    badgeClassName: "bg-emerald-600 text-white hover:bg-emerald-600/80",
    description: "Order delivered.",
  },
  rto: {
    value: "rto",
    label: "RTO",
    badgeClassName: "bg-destructive/10 text-destructive",
    description: "Returned to origin.",
  },
  cancelled: {
    value: "cancelled",
    label: "Cancelled",
    badgeClassName: "bg-destructive/10 text-destructive",
    description: "Order cancelled.",
  },
};

export const ORDER_STATUS_OPTIONS: { label: string; value: OrderStatus }[] =
  Object.values(ORDER_STATUS_META).map((meta) => ({
    label: meta.label,
    value: meta.value,
  }));

export interface OrderStatusActionMeta {
  to: OrderStatus;
  label: string;
  confirmTitle: string;
  confirmDescription: string;
  destructive?: boolean;
}

export const CANCEL_ORDER_ACTION: OrderStatusActionMeta = {
  to: "cancelled",
  label: "Cancel Order",
  confirmTitle: "Cancel this Order?",
  confirmDescription:
    "Any active stock reservation will be released. Physical stock will not be returned.",
  destructive: true,
};

export const RTO_ORDER_ACTION: OrderStatusActionMeta = {
  to: "rto",
  label: "Mark RTO",
  confirmTitle: "Mark this Order as RTO?",
  confirmDescription:
    "This means the dispatched shipment has returned to origin.",
  destructive: true,
};

const ORDER_STATUS_TRANSITIONS: Partial<Record<OrderStatus, OrderStatusActionMeta[]>> = {
  intake: [
    {
      to: "to_procure",
      label: "Move to Procurement",
      confirmTitle: "Move to Procurement?",
      confirmDescription: "This order will wait for the required books to be procured.",
    },
    {
      to: "to_pack",
      label: "Move to Packing",
      confirmTitle: "Move to Packing?",
      confirmDescription: "Move this order to packing only if all required books are available.",
    },
    CANCEL_ORDER_ACTION,
  ],
  to_procure: [
    {
      to: "to_pack",
      label: "Move to Packing",
      confirmTitle: "Move to Packing?",
      confirmDescription: "Move this order to packing only if all required books are available.",
    },
    CANCEL_ORDER_ACTION,
  ],
  to_pack: [
    {
      to: "packed",
      label: "Mark Packed",
      confirmTitle: "Mark as Packed?",
      confirmDescription: "Confirm that all items in this order have been packed.",
    },
    CANCEL_ORDER_ACTION,
  ],
  packed: [
    {
      to: "dispatched",
      label: "Mark Dispatched",
      confirmTitle: "Mark as Dispatched?",
      confirmDescription: "Confirm that this order has been dispatched.",
    },
  ],
  dispatched: [
    {
      to: "delivered",
      label: "Mark Delivered",
      confirmTitle: "Mark as Delivered?",
      confirmDescription: "Confirm that this order has been delivered to the customer.",
    },
    RTO_ORDER_ACTION,
  ],
};

export function getOrderStatusActions(
  status: OrderStatus | null | undefined
): OrderStatusActionMeta[] {
  if (!status) return [];
  return ORDER_STATUS_TRANSITIONS[status] ?? [];
}

export function getAvailabilitySummary(
  status: OrderAvailabilityStatus
): { label: string; detail: string; tone: "success" | "warning" | "danger" } {
  switch (status) {
    case "fully_available":
      return { label: "Fully Available", detail: "All items are available.", tone: "success" };
    case "partially_available":
      return { label: "Partially Available", detail: "Some items are still required.", tone: "warning" };
    case "unavailable":
      return { label: "Unavailable", detail: "Required items are not available.", tone: "danger" };
    case "unverifiable":
      return {
        label: "Cannot Verify",
        detail: "Availability cannot be verified for one or more items.",
        tone: "danger",
      };
  }
}

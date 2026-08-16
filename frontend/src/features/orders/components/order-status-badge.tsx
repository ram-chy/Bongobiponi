import { Badge } from "@/components/ui/badge";
import { ORDER_STATUS_META } from "@/features/orders/order-status-meta";
import type { OrderStatus } from "@/types/order";

interface OrderStatusBadgeProps {
  status: OrderStatus | null | undefined;
}

export function OrderStatusBadge({ status }: OrderStatusBadgeProps) {
  if (!status) {
    return <Badge variant="outline">Unknown</Badge>;
  }

  const meta = ORDER_STATUS_META[status];

  return (
    <Badge variant="default" className={meta.badgeClassName}>
      {meta.label}
    </Badge>
  );
}

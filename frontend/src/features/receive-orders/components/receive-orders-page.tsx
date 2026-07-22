"use client";

import { EntityPage } from "@/components/common/entity-page";
import { Badge } from "@/components/ui/badge";
import type { ReceiveOrder } from "@/types/receive-order";
import type { EntityConfig, ColumnDef } from "@/types/entity";

const statusLabels: Record<string, string> = {
  draft: "Draft",
  approved: "Approved",
  partially_received: "Partially Received",
  completed: "Completed",
  cancelled: "Cancelled",
};

const statusVariants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
  draft: "outline",
  approved: "default",
  partially_received: "secondary",
  completed: "default",
  cancelled: "destructive",
};

const statusClasses: Record<string, string> = {
  draft: "",
  approved: "bg-blue-600 hover:bg-blue-600/80",
  partially_received: "bg-amber-500 hover:bg-amber-500/80",
  completed: "bg-emerald-600 hover:bg-emerald-600/80",
  cancelled: "",
};

const columns: ColumnDef<ReceiveOrder>[] = [
  {
    id: "order_no",
    header: "Order No",
    accessorKey: "order_no",
    sortable: true,
  },
  {
    id: "supplier",
    header: "Supplier",
    accessorKey: "supplier",
    cell: (row) => row.supplier?.company_name ?? "-",
  },
  {
    id: "customer",
    header: "Order Placer",
    accessorKey: "customer",
    cell: (row) => row.customer?.company_name ?? row.customer?.name ?? "-",
  },
  {
    id: "expected_delivery_date",
    header: "Expected Delivery",
    accessorKey: "expected_delivery_date",
    sortable: true,
    cell: (row) =>
      row.expected_delivery_date
        ? new Date(row.expected_delivery_date).toLocaleDateString()
        : "-",
  },
  {
    id: "reference_no",
    header: "Reference No",
    accessorKey: "reference_no",
  },
  {
    id: "status",
    header: "Status",
    accessorKey: "status",
    sortable: true,
    cell: (row) => (
      <Badge
        variant={statusVariants[row.status] ?? "outline"}
        className={statusClasses[row.status]}
      >
        {statusLabels[row.status] ?? row.status}
      </Badge>
    ),
  },
  {
    id: "total_amount",
    header: "Total Amount",
    accessorKey: "total_amount",
    sortable: false,
    cell: (row) => {
      const amount = row.total_amount ?? 0;
      return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
      }).format(amount);
    },
  },
  {
    id: "created_at",
    header: "Created At",
    accessorKey: "created_at",
    sortable: true,
    cell: (row) =>
      row.created_at
        ? new Date(row.created_at).toLocaleDateString()
        : "-",
  },
];

const receiveOrderConfig: EntityConfig<ReceiveOrder> = {
  title: "Receive Orders",
  description: "Manage purchase receive orders from suppliers.",
  endpoint: "/receive-orders",
  createRoute: "/receive-orders/create",
  viewRoute: "/receive-orders/:id",
  editRoute: "/receive-orders/:id/edit",
  columns,
  searchPlaceholder: "Search receive orders...",
  defaultSort: { id: "created_at", desc: true },
  perPage: 15,
};

export function ReceiveOrdersPage() {
  return (
    <EntityPage
      config={receiveOrderConfig}
      breadcrumbItems={[{ label: "Sales" }, { label: "Receive Orders" }]}
    />
  );
}

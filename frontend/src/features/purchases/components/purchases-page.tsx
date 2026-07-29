"use client";

import { EntityPage } from "@/components/common/entity-page";
import { Badge } from "@/components/ui/badge";
import type { Purchase } from "@/types/purchase";
import type { EntityConfig, ColumnDef } from "@/types/entity";

const statusLabels: Record<string, string> = {
  draft: "Draft",
  confirmed: "Confirmed",
  cancelled: "Cancelled",
};

const statusVariants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
  draft: "outline",
  confirmed: "default",
  cancelled: "destructive",
};

const statusClasses: Record<string, string> = {
  draft: "",
  confirmed: "bg-blue-600 hover:bg-blue-600/80",
  cancelled: "",
};

const purchaseTypeLabels: Record<string, string> = {
  manual: "Manual",
};

const columns: ColumnDef<Purchase>[] = [
  {
    id: "purchase_no",
    header: "Purchase No",
    accessorKey: "purchase_no",
    sortable: true,
  },
  {
    id: "supplier",
    header: "Supplier",
    accessorKey: "supplier",
    cell: (row) => row.supplier?.company_name ?? "-",
  },
  {
    id: "purchase_type",
    header: "Type",
    accessorKey: "purchase_type",
    cell: (row) => (
      <Badge variant="outline">
        {purchaseTypeLabels[row.purchase_type] ?? row.purchase_type}
      </Badge>
    ),
  },
  {
    id: "invoice_no",
    header: "Invoice No",
    accessorKey: "invoice_no",
    cell: (row) => row.invoice_no ?? "-",
  },
  {
    id: "purchase_date",
    header: "Purchase Date",
    accessorKey: "purchase_date",
    sortable: true,
    cell: (row) =>
      row.purchase_date
        ? new Date(row.purchase_date).toLocaleDateString()
        : "-",
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

const purchaseConfig: EntityConfig<Purchase> = {
  title: "Purchases",
  description: "Manage purchase records from suppliers.",
  endpoint: "/purchases",
  createRoute: "/purchases/create",
  viewRoute: "/purchases/:id",
  editRoute: "/purchases/:id/edit",
  columns,
  searchPlaceholder: "Search purchases...",
  defaultSort: { id: "created_at", desc: true },
  perPage: 15,
};

export function PurchasesPage() {
  return (
    <EntityPage
      config={purchaseConfig}
      breadcrumbItems={[{ label: "Sales" }, { label: "Purchases" }]}
    />
  );
}

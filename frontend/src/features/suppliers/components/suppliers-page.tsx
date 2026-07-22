"use client";

import { EntityPage } from "@/components/common/entity-page";
import { Badge } from "@/components/ui/badge";
import type { Supplier } from "@/types/supplier";
import type { EntityConfig, ColumnDef } from "@/types/entity";

const columns: ColumnDef<Supplier>[] = [
  {
    id: "name",
    header: "Name",
    accessorKey: "name",
    sortable: true,
  },
  {
    id: "company_name",
    header: "Company Name",
    accessorKey: "company_name",
    sortable: true,
  },
  {
    id: "phone",
    header: "Phone",
    accessorKey: "phone",
  },
  {
    id: "email",
    header: "Email",
    accessorKey: "email",
  },
  {
    id: "gst_number",
    header: "GST Number",
    accessorKey: "gst_number",
  },
  {
    id: "status",
    header: "Status",
    accessorKey: "status",
    sortable: true,
    cell: (row) => (
      <Badge
        variant={row.status ? "default" : "destructive"}
        className={row.status ? "bg-emerald-600 hover:bg-emerald-600/80" : undefined}
      >
        {row.status ? "ACTIVE" : "INACTIVE"}
      </Badge>
    ),
  },
];

const supplierConfig: EntityConfig<Supplier> = {
  title: "Supplier Management",
  description: "Manage supplier information.",
  endpoint: "/suppliers",
  createRoute: "/suppliers/create",
  viewRoute: "/suppliers/:id",
  editRoute: "/suppliers/:id/edit",
  columns,
  searchPlaceholder: "Search suppliers...",
  defaultSort: { id: "created_at", desc: true },
  perPage: 15,
};

export function SuppliersPage() {
  return (
    <EntityPage
      config={supplierConfig}
      breadcrumbItems={[{ label: "Master Data" }, { label: "Suppliers" }]}
    />
  );
}

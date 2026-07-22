"use client";

import { EntityPage } from "@/components/common/entity-page";
import { Badge } from "@/components/ui/badge";
import type { Customer } from "@/types/customer";
import type { EntityConfig, ColumnDef } from "@/types/entity";

const columns: ColumnDef<Customer>[] = [
  {
    id: "customer_code",
    header: "Code",
    accessorKey: "customer_code",
    sortable: true,
  },
  {
    id: "company_name",
    header: "Company Name",
    accessorKey: "company_name",
    sortable: true,
  },
  {
    id: "name",
    header: "Contact Person",
    accessorKey: "name",
    sortable: true,
  },
  {
    id: "phone",
    header: "Mobile",
    accessorKey: "phone",
  },
  {
    id: "email",
    header: "Email",
    accessorKey: "email",
  },
  {
    id: "city",
    header: "City",
    accessorKey: "city",
    sortable: true,
  },
  {
    id: "status",
    header: "Status",
    accessorKey: "status",
    sortable: true,
    cell: (row) => (
      <Badge
        variant={row.status === "active" ? "default" : "destructive"}
        className={row.status === "active" ? "bg-emerald-600 hover:bg-emerald-600/80" : undefined}
      >
        {row.status.toUpperCase()}
      </Badge>
    ),
  },
];

const customerConfig: EntityConfig<Customer> = {
  title: "Customer Management",
  description: "Manage customer information.",
  endpoint: "/customers",
  createRoute: "/customers/create",
  editRoute: "/customers/:id/edit",
  columns,
  searchPlaceholder: "Search customers...",
  defaultSort: { id: "created_at", desc: true },
  perPage: 10,
};

export function CustomersPage() {
  return (
    <EntityPage
      config={customerConfig}
      breadcrumbItems={[{ label: "Registration" }, { label: "Customers" }]}
    />
  );
}

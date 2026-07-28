"use client";

import { EntityPage } from "@/components/common/entity-page";
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
    id: "name",
    header: "Name",
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

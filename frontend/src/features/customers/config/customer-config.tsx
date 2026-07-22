import type { Customer } from "@/types/customer";
import type { EntityConfig, ColumnDef } from "@/types/entity";
import { Badge } from "@/components/ui/badge";

const columns: ColumnDef<Customer>[] = [
  {
    id: "customer_code",
    header: "Code",
    accessorKey: "customer_code",
    sortable: true,
  },
  {
    id: "name",
    header: "Contact Person",
    accessorKey: "name",
    sortable: true,
    cell: (row) => (
      <div>
        <p className="font-medium">{row.name}</p>
        {row.company_name && (
          <p className="text-xs text-muted-foreground">{row.company_name}</p>
        )}
      </div>
    ),
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
        variant={row.status === "active" ? "default" : "secondary"}
        className={row.status === "active" ? "bg-emerald-600 hover:bg-emerald-600/80" : undefined}
      >
        {row.status}
      </Badge>
    ),
  },
];

export const customerConfig: EntityConfig<Customer> = {
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

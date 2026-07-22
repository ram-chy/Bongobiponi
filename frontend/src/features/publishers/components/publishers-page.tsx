"use client";

import { EntityPage } from "@/components/common/entity-page";
import { Badge } from "@/components/ui/badge";
import type { Publisher } from "@/types/publisher";
import type { EntityConfig, ColumnDef } from "@/types/entity";

const columns: ColumnDef<Publisher>[] = [
  {
    id: "name",
    header: "Name",
    accessorKey: "name",
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

const publisherConfig: EntityConfig<Publisher> = {
  title: "Publisher Management",
  description: "Manage publisher information.",
  endpoint: "/publishers",
  createRoute: "/publishers/create",
  viewRoute: "/publishers/:id",
  editRoute: "/publishers/:id/edit",
  columns,
  searchPlaceholder: "Search publishers...",
  defaultSort: { id: "created_at", desc: true },
  perPage: 15,
};

export function PublishersPage() {
  return (
    <EntityPage
      config={publisherConfig}
      breadcrumbItems={[{ label: "Master Data" }, { label: "Publishers" }]}
    />
  );
}

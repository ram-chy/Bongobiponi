"use client";

import { EntityPage } from "@/components/common/entity-page";
import { Badge } from "@/components/ui/badge";
import type { Author } from "@/types/author";
import type { EntityConfig, ColumnDef } from "@/types/entity";

const columns: ColumnDef<Author>[] = [
  {
    id: "name",
    header: "Name",
    accessorKey: "name",
    sortable: true,
  },
  {
    id: "country",
    header: "Country",
    accessorKey: "country",
    sortable: true,
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

const authorConfig: EntityConfig<Author> = {
  title: "Author Management",
  description: "Manage author information.",
  endpoint: "/authors",
  createRoute: "/authors/create",
  viewRoute: "/authors/:id",
  editRoute: "/authors/:id/edit",
  columns,
  searchPlaceholder: "Search authors...",
  defaultSort: { id: "created_at", desc: true },
  perPage: 15,
};

export function AuthorsPage() {
  return (
    <EntityPage
      config={authorConfig}
      breadcrumbItems={[{ label: "Master Data" }, { label: "Authors" }]}
    />
  );
}

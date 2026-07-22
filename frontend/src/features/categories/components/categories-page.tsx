"use client";

import { EntityPage } from "@/components/common/entity-page";
import { Badge } from "@/components/ui/badge";
import type { Category } from "@/types/category";
import type { EntityConfig, ColumnDef } from "@/types/entity";

const columns: ColumnDef<Category>[] = [
  {
    id: "name",
    header: "Name",
    accessorKey: "name",
    sortable: true,
  },
  {
    id: "parent",
    header: "Parent Category",
    accessorKey: "parent_id",
    cell: (row) => row.parent?.name ?? "—",
  },
  {
    id: "description",
    header: "Description",
    accessorKey: "description",
  },
  {
    id: "status",
    header: "Status",
    accessorKey: "status",
    sortable: true,
    cell: (row) => (
      <Badge
        variant={row.status ? "default" : "destructive"}
        className={
          row.status ? "bg-emerald-600 hover:bg-emerald-600/80" : undefined
        }
      >
        {row.status ? "ACTIVE" : "INACTIVE"}
      </Badge>
    ),
  },
];

const categoryConfig: EntityConfig<Category> = {
  title: "Category Management",
  description: "Manage book categories.",
  endpoint: "/categories",
  createRoute: "/categories/create",
  viewRoute: "/categories/:id",
  editRoute: "/categories/:id/edit",
  columns,
  searchPlaceholder: "Search categories...",
  defaultSort: { id: "created_at", desc: true },
  perPage: 15,
};

export function CategoriesPage() {
  return (
    <EntityPage
      config={categoryConfig}
      breadcrumbItems={[{ label: "Master Data" }, { label: "Categories" }]}
    />
  );
}

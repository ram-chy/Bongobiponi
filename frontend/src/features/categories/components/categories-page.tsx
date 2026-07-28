"use client";

import { EntityPage } from "@/components/common/entity-page";
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
    id: "description",
    header: "Description",
    accessorKey: "description",
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

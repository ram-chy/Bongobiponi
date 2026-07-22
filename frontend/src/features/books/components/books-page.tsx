"use client";

import { EntityPage } from "@/components/common/entity-page";
import { Badge } from "@/components/ui/badge";
import type { Book } from "@/types/book";
import type { EntityConfig, ColumnDef } from "@/types/entity";

const columns: ColumnDef<Book>[] = [
  {
    id: "title",
    header: "Title",
    accessorKey: "title",
    sortable: true,
  },
  {
    id: "isbn",
    header: "ISBN",
    accessorKey: "isbn",
  },
  {
    id: "publisher",
    header: "Publisher",
    accessorKey: "publisher_id",
    cell: (row) => row.publisher?.name ?? "—",
  },
  {
    id: "category",
    header: "Category",
    accessorKey: "category_id",
    cell: (row) => row.category?.name ?? "—",
  },
  {
    id: "selling_price",
    header: "Price",
    accessorKey: "selling_price",
    cell: (row) => `₹${Number(row.selling_price).toLocaleString()}`,
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

const bookConfig: EntityConfig<Book> = {
  title: "Book Management",
  description: "Manage book inventory.",
  endpoint: "/books",
  createRoute: "/books/create",
  viewRoute: "/books/:id",
  editRoute: "/books/:id/edit",
  columns,
  searchPlaceholder: "Search books...",
  defaultSort: { id: "created_at", desc: true },
  perPage: 15,
};

export function BooksPage() {
  return (
    <EntityPage
      config={bookConfig}
      breadcrumbItems={[{ label: "Master Data" }, { label: "Books" }]}
    />
  );
}

"use client";

import { EntityPage } from "@/components/common/entity-page";
import { BookOpen } from "lucide-react";
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
    id: "cover_image",
    header: "Cover",
    accessorKey: "cover_image",
    cell: (row) => {
      if (!row.cover_image) {
        return (
          <div className="size-10 rounded bg-muted flex items-center justify-center">
            <BookOpen className="size-4 text-muted-foreground" />
          </div>
        );
      }
      const storageUrl =
        (process.env.NEXT_PUBLIC_API_URL ?? "").replace(/\/api$/, "") +
        "/storage/";
      return (
        // eslint-disable-next-line @next/next/no-img-element
        <img
          src={storageUrl + row.cover_image}
          alt={row.title}
          className="size-10 object-cover rounded shrink-0"
        />
      );
    },
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

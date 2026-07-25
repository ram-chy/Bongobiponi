"use client";

import { useMemo } from "react";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { BookForm } from "@/features/books/components/book-form";
import { useBook } from "@/features/books/hooks/use-book";
import { Loader2 } from "lucide-react";

export function EditBookPage({ id }: { id: number }) {
  const { data: book, isLoading } = useBook(id);

  const defaultValues = useMemo(
    () =>
      book
        ? {
            isbn: book.isbn ?? "",
            barcode: book.barcode ?? "",
            title: book.title,
            subtitle: book.subtitle ?? "",
            publisher_id: book.publisher_id
              ? String(book.publisher_id)
              : "",
            category_id: book.category_id
              ? String(book.category_id)
              : "",
            edition: book.edition ?? "",
            language: book.language ?? "",
            purchase_price: String(book.purchase_price),
            selling_price: String(book.selling_price),
            minimum_stock: String(book.minimum_stock),
            authors: book.authors?.map((a) => a.id) ?? [],
            cover_image: book.cover_image ?? "",
            description: book.description ?? "",
            status: book.status,
          }
        : undefined,
    [book]
  );

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Books", href: "/books" },
          { label: "Edit Book" },
        ]}
      />
      <PageHeader title="Edit Book" />
      <BookForm id={id} defaultValues={defaultValues} />
    </div>
  );
}

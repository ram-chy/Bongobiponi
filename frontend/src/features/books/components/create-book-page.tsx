"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { BookForm } from "@/features/books/components/book-form";

export function CreateBookPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Books", href: "/books" },
          { label: "Create Book" },
        ]}
      />
      <PageHeader title="Create Book" />
      <BookForm />
    </div>
  );
}

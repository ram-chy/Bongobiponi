"use client";

import Link from "next/link";
import { ArrowLeft, Pencil } from "lucide-react";
import { Button, buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import { useBook } from "@/features/books/hooks/use-book";
import { cn } from "@/lib/utils";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

export function ViewBookPage({ id }: { id: number }) {
  const { data: book, isLoading } = useBook(id);

  if (isLoading) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Loading...</div>;
  }

  if (!book) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Book not found.</div>;
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Books", href: "/books" },
          { label: book.title },
        ]}
      />
      <PageHeader
        title={book.title}
        description={book.subtitle || "Book details"}
        actions={
          <div className="flex items-center gap-2">
            <Link
              href={`/books/${id}/edit`}
              className={cn(buttonVariants({ variant: "outline" }), "gap-1.5")}
            >
              <Pencil className="size-4" />
              Edit
            </Link>
          </div>
        }
      />

      <div className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Book Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Title</p>
                <p className="font-medium">{book.title}</p>
              </div>
              {book.subtitle && (
                <div>
                  <p className="text-sm text-muted-foreground">Subtitle</p>
                  <p className="font-medium">{book.subtitle}</p>
                </div>
              )}
              <div>
                <p className="text-sm text-muted-foreground">ISBN</p>
                <p className="font-medium">{book.isbn || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Barcode</p>
                <p className="font-medium">{book.barcode || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Publisher</p>
                <p className="font-medium">{book.publisher?.name || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Category</p>
                <p className="font-medium">{book.category?.name || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Edition</p>
                <p className="font-medium">{book.edition || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Language</p>
                <p className="font-medium">{book.language || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Status</p>
                <Badge
                  variant={book.status ? "default" : "destructive"}
                  className={book.status ? "bg-emerald-600 hover:bg-emerald-600/80" : undefined}
                >
                  {book.status ? "ACTIVE" : "INACTIVE"}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Pricing & Stock</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-3">
              <div>
                <p className="text-sm text-muted-foreground">Purchase Price</p>
                <p className="font-medium">₹{Number(book.purchase_price).toLocaleString()}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Selling Price</p>
                <p className="font-medium">₹{Number(book.selling_price).toLocaleString()}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Minimum Stock</p>
                <p className="font-medium">{book.minimum_stock}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {book.authors && book.authors.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Authors</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap gap-2">
                {book.authors.map((author) => (
                  <Badge key={author.id} variant="secondary">
                    {author.name}
                  </Badge>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {book.description && (
          <Card>
            <CardHeader>
              <CardTitle>Description</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm whitespace-pre-wrap">{book.description}</p>
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle>System Info</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Created By</p>
                <p className="font-medium">
                  {book.created_by
                    ? `${book.created_by.first_name} ${book.created_by.last_name}`
                    : "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Created At</p>
                <p className="font-medium">{fmtDate(book.created_at)}</p>
              </div>
              {book.updated_by && (
                <>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated By</p>
                    <p className="font-medium">
                      {book.updated_by.first_name} {book.updated_by.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated At</p>
                    <p className="font-medium">{fmtDate(book.updated_at)}</p>
                  </div>
                </>
              )}
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button type="button" variant="outline" onClick={() => window.history.back()}>
            <ArrowLeft className="size-4" />
            Back
          </Button>
        </div>
      </div>
    </div>
  );
}

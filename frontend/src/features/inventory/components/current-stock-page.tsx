"use client";

import { useMemo } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { bookService } from "@/services/book-service";
import { Eye } from "lucide-react";

function getStockStatus(
  currentQty: number,
  minStock: number
): { label: string; className: string } {
  if (currentQty === 0) {
    return { label: "Out of Stock", className: "bg-red-600 hover:bg-red-600/80" };
  }
  if (currentQty <= minStock) {
    return { label: "Low Stock", className: "bg-amber-500 hover:bg-amber-500/80" };
  }
  return { label: "In Stock", className: "bg-emerald-600 hover:bg-emerald-600/80" };
}

export function CurrentStockPage() {
  const { data: booksData, isLoading } = useQuery({
    queryKey: ["/books", "stock-list"],
    queryFn: async () => {
      const response = await bookService.list({ per_page: 1000 });
      return response.data.data;
    },
  });

  const books = useMemo(() => booksData ?? [], [booksData]);

  const stats = useMemo(() => {
    const total = books.length;
    const totalQty = books.reduce(
      (sum: number, b: { stock?: { current_quantity?: number } }) =>
        sum + (b.stock?.current_quantity ?? 0),
      0
    );
    const low = books.filter(
      (b: { stock?: { current_quantity?: number }; minimum_stock?: number }) => {
        const qty = b.stock?.current_quantity ?? 0;
        return qty > 0 && qty <= (b.minimum_stock ?? 0);
      }
    ).length;
    const out = books.filter(
      (b: { stock?: { current_quantity?: number } }) =>
        (b.stock?.current_quantity ?? 0) === 0
    ).length;
    return { total, totalQty, low, out };
  }, [books]);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Loading...
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb items={[{ label: "Inventory" }, { label: "Current Stock" }]} />
      <PageHeader
        title="Current Stock"
        description="View current stock levels for all books."
      />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <Card>
          <CardContent className="pt-6">
            <p className="text-sm text-muted-foreground">Total Books</p>
            <p className="text-2xl font-bold">{stats.total}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <p className="text-sm text-muted-foreground">Total Quantity</p>
            <p className="text-2xl font-bold">{stats.totalQty}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <p className="text-sm text-muted-foreground">Low Stock</p>
            <p className="text-2xl font-bold text-amber-600">{stats.low}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <p className="text-sm text-muted-foreground">Out of Stock</p>
            <p className="text-2xl font-bold text-red-600">{stats.out}</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Stock List</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>ISBN</TableHead>
                  <TableHead>Title</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Publisher</TableHead>
                  <TableHead className="text-right">Current Qty</TableHead>
                  <TableHead className="text-right">Min Stock</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="w-[60px]" />
                </TableRow>
              </TableHeader>
              <TableBody>
                {books.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                      No books found.
                    </TableCell>
                  </TableRow>
                ) : (
                  books.map((book: {
                    id: number;
                    title: string;
                    isbn: string;
                    minimum_stock: number;
                    stock?: { current_quantity: number } | null;
                    category?: { name: string } | null;
                    publisher?: { name: string } | null;
                  }) => {
                    const qty = book.stock?.current_quantity ?? 0;
                    const status = getStockStatus(qty, book.minimum_stock ?? 0);
                    return (
                      <TableRow key={book.id}>
                        <TableCell className="font-mono text-xs">
                          {book.isbn || "-"}
                        </TableCell>
                        <TableCell className="font-medium">{book.title}</TableCell>
                        <TableCell>{book.category?.name ?? "-"}</TableCell>
                        <TableCell>{book.publisher?.name ?? "-"}</TableCell>
                        <TableCell className="text-right font-medium">
                          {qty}
                        </TableCell>
                        <TableCell className="text-right">
                          {book.minimum_stock ?? 0}
                        </TableCell>
                        <TableCell>
                          <Badge variant="outline" className={status.className}>
                            {status.label}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          <Link href={`/inventory/stock/${book.id}`}>
                            <Button variant="ghost" size="icon">
                              <Eye className="size-4" />
                            </Button>
                          </Link>
                        </TableCell>
                      </TableRow>
                    );
                  })
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

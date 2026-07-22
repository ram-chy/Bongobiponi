"use client";

import { useMemo } from "react";
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
import { BookOpen, Package, AlertTriangle, XCircle } from "lucide-react";
import { bookService } from "@/services/book-service";
import { inventoryService } from "@/services/inventory-service";

const transactionTypeLabels: Record<string, string> = {
  opening: "Opening Stock",
  purchase: "Purchase",
  purchase_return: "Purchase Return",
  sale: "Sale",
  sale_return: "Sale Return",
  adjustment: "Adjustment",
  damage: "Damage",
  transfer_in: "Transfer In",
  transfer_out: "Transfer Out",
};

const transactionTypeBadge: Record<string, string> = {
  opening: "bg-blue-600 hover:bg-blue-600/80",
  purchase: "bg-emerald-600 hover:bg-emerald-600/80",
  sale: "bg-amber-500 hover:bg-amber-500/80",
  adjustment: "bg-purple-600 hover:bg-purple-600/80",
  damage: "bg-red-600 hover:bg-red-600/80",
};

export function InventoryDashboardPage() {
  const { data: booksData, isLoading: booksLoading } = useQuery({
    queryKey: ["/books", "dashboard"],
    queryFn: async () => {
      const response = await bookService.list({ per_page: 1000 });
      return response.data.data;
    },
  });

  const { data: transactionsData, isLoading: txLoading } = useQuery({
    queryKey: ["/inventory", "dashboard-recent"],
    queryFn: async () => {
      const response = await inventoryService.list({ per_page: 10, sort: "created_at", direction: "desc" });
      return response.data.data;
    },
  });

  const stats = useMemo(() => {
    const books = booksData ?? [];
    const totalBooks = books.length;
    const totalStock = books.reduce(
      (sum: number, b: { stock?: { current_quantity?: number } }) =>
        sum + (b.stock?.current_quantity ?? 0),
      0
    );
    const lowStock = books.filter(
      (b: { stock?: { current_quantity?: number }; minimum_stock?: number }) => {
        const qty = b.stock?.current_quantity ?? 0;
        return qty > 0 && qty <= (b.minimum_stock ?? 0);
      }
    ).length;
    const outOfStock = books.filter(
      (b: { stock?: { current_quantity?: number } }) =>
        (b.stock?.current_quantity ?? 0) === 0
    ).length;

    return { totalBooks, totalStock, lowStock, outOfStock };
  }, [booksData]);

  if (booksLoading || txLoading) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Loading...
      </div>
    );
  }

  const recentTransactions = transactionsData ?? [];

  return (
    <div>
      <PageBreadcrumb items={[{ label: "Inventory" }, { label: "Dashboard" }]} />
      <PageHeader
        title="Inventory Dashboard"
        description="Overview of stock levels and recent inventory activity."
      />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                <BookOpen className="size-5 text-blue-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Total Books</p>
                <p className="text-2xl font-bold">{stats.totalBooks}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-900/30">
                <Package className="size-5 text-emerald-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Total Stock</p>
                <p className="text-2xl font-bold">{stats.totalStock}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/30">
                <AlertTriangle className="size-5 text-amber-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Low Stock</p>
                <p className="text-2xl font-bold">{stats.lowStock}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-3">
              <div className="rounded-lg bg-red-100 p-2 dark:bg-red-900/30">
                <XCircle className="size-5 text-red-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Out of Stock</p>
                <p className="text-2xl font-bold">{stats.outOfStock}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Recent Transactions</CardTitle>
        </CardHeader>
        <CardContent>
          {recentTransactions.length === 0 ? (
            <p className="text-sm text-muted-foreground text-center py-8">
              No transactions yet.
            </p>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Transaction No</TableHead>
                    <TableHead>Book</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead className="text-right">In</TableHead>
                    <TableHead className="text-right">Out</TableHead>
                    <TableHead className="text-right">Balance</TableHead>
                    <TableHead>Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {recentTransactions.map((tx: {
                    id: number;
                    transaction_no: string;
                    transaction_type: string;
                    transaction_type_label: string;
                    book?: { title: string } | null;
                    quantity_in: number;
                    quantity_out: number;
                    balance_after: number;
                    transaction_date: string;
                  }) => (
                    <TableRow key={tx.id}>
                      <TableCell className="font-mono text-xs">
                        {tx.transaction_no}
                      </TableCell>
                      <TableCell>{tx.book?.title ?? "-"}</TableCell>
                      <TableCell>
                        <Badge
                          variant="outline"
                          className={transactionTypeBadge[tx.transaction_type] ?? ""}
                        >
                          {tx.transaction_type_label}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right text-emerald-600">
                        {tx.quantity_in > 0 ? `+${tx.quantity_in}` : "-"}
                      </TableCell>
                      <TableCell className="text-right text-red-600">
                        {tx.quantity_out > 0 ? `-${tx.quantity_out}` : "-"}
                      </TableCell>
                      <TableCell className="text-right font-medium">
                        {tx.balance_after}
                      </TableCell>
                      <TableCell>
                        {tx.transaction_date
                          ? new Date(tx.transaction_date).toLocaleDateString()
                          : "-"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

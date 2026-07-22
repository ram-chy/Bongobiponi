"use client";

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
import { inventoryService } from "@/services/inventory-service";
import { ArrowLeft } from "lucide-react";

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

function getStockStatus(qty: number, min: number) {
  if (qty === 0) return { label: "Out of Stock", className: "bg-red-600 hover:bg-red-600/80" };
  if (qty <= min) return { label: "Low Stock", className: "bg-amber-500 hover:bg-amber-500/80" };
  return { label: "In Stock", className: "bg-emerald-600 hover:bg-emerald-600/80" };
}

export function BookInventoryPage({ bookId }: { bookId: number }) {
  const { data: book, isLoading: bookLoading } = useQuery({
    queryKey: ["/books", bookId],
    queryFn: async () => {
      const response = await bookService.get(bookId);
      return response.data.data;
    },
    enabled: !!bookId,
  });

  const { data: stock, isLoading: stockLoading } = useQuery({
    queryKey: ["/inventory/stock", bookId],
    queryFn: async () => {
      const response = await inventoryService.getStock(bookId);
      return response.data.data;
    },
    enabled: !!bookId,
  });

  const { data: ledger, isLoading: ledgerLoading } = useQuery({
    queryKey: ["/inventory/ledger", bookId],
    queryFn: async () => {
      const response = await inventoryService.getLedger(bookId);
      return response.data.data;
    },
    enabled: !!bookId,
  });

  if (bookLoading || stockLoading || ledgerLoading) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Loading...
      </div>
    );
  }

  if (!book) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Book not found.
      </div>
    );
  }

  const currentQty = (stock as { current_quantity?: number } | undefined)?.current_quantity ?? 0;
  const status = getStockStatus(currentQty, book.minimum_stock ?? 0);
  const transactions = ledger ?? [];

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Inventory" },
          { label: "Current Stock", href: "/inventory/stock" },
          { label: book.title },
        ]}
      />
      <PageHeader
        title={book.title}
        description={`ISBN: ${book.isbn || "N/A"}`}
        actions={
          <Link href="/inventory/stock">
            <Button variant="outline" className="gap-1.5">
              <ArrowLeft className="size-4" />
              Back
            </Button>
          </Link>
        }
      />

      <div className="grid gap-6">
        <div className="grid gap-4 sm:grid-cols-3">
          <Card>
            <CardContent className="pt-6">
              <p className="text-sm text-muted-foreground">Current Quantity</p>
              <p className="text-2xl font-bold">{currentQty}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <p className="text-sm text-muted-foreground">Minimum Stock</p>
              <p className="text-2xl font-bold">{book.minimum_stock ?? 0}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <p className="text-sm text-muted-foreground">Status</p>
              <Badge variant="outline" className={status.className}>
                {status.label}
              </Badge>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Transaction History</CardTitle>
          </CardHeader>
          <CardContent>
            {transactions.length === 0 ? (
              <p className="text-sm text-muted-foreground text-center py-8">
                No transactions yet.
              </p>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Transaction No</TableHead>
                      <TableHead>Type</TableHead>
                      <TableHead className="text-right">In</TableHead>
                      <TableHead className="text-right">Out</TableHead>
                      <TableHead className="text-right">Balance</TableHead>
                      <TableHead>Date</TableHead>
                      <TableHead>Remarks</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {transactions.map((tx: {
                      id: number;
                      transaction_no: string;
                      transaction_type: string;
                      transaction_type_label: string;
                      quantity_in: number;
                      quantity_out: number;
                      balance_after: number;
                      transaction_date: string;
                      remarks: string | null;
                    }) => (
                      <TableRow key={tx.id}>
                        <TableCell className="font-mono text-xs">
                          {tx.transaction_no}
                        </TableCell>
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
                        <TableCell className="text-sm text-muted-foreground">
                          {tx.remarks || "-"}
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
    </div>
  );
}

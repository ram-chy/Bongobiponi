"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { inventoryService } from "@/services/inventory-service";
import { bookService } from "@/services/book-service";
import { useInventoryList } from "@/features/inventory/hooks/use-inventory-list";
import { Search, ChevronLeft, ChevronRight } from "lucide-react";

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

export function InventoryLedgerPage() {
  const [search, setSearch] = useState("");
  const [searchDebounced, setSearchDebounced] = useState("");
  const [page, setPage] = useState(1);
  const [transactionType, setTransactionType] = useState<string>("all");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  const { data: booksData } = useQuery({
    queryKey: ["/books", "ledger-filter"],
    queryFn: async () => {
      const response = await bookService.list({ per_page: 1000 });
      return response.data.data;
    },
  });

  const { data, isLoading } = useInventoryList({
    search: searchDebounced,
    page,
    per_page: 20,
    transaction_type: transactionType === "all" ? undefined : transactionType,
    date_from: dateFrom || undefined,
    date_to: dateTo || undefined,
  });

  const transactions = data?.data ?? [];
  const meta = data?.meta;

  const handleSearch = () => {
    setSearchDebounced(search);
    setPage(1);
  };

  return (
    <div>
      <PageBreadcrumb items={[{ label: "Inventory" }, { label: "Inventory Ledger" }]} />
      <PageHeader
        title="Inventory Ledger"
        description="View all inventory transactions."
      />

      <Card className="mb-6">
        <CardContent className="pt-6">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div className="space-y-2">
              <Label>Search</Label>
              <div className="flex gap-2">
                <Input
                  placeholder="Search transactions..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  onKeyDown={(e) => e.key === "Enter" && handleSearch()}
                />
                <Button variant="outline" size="icon" onClick={handleSearch}>
                  <Search className="size-4" />
                </Button>
              </div>
            </div>
            <div className="space-y-2">
              <Label>Transaction Type</Label>
              <Select
                value={transactionType}
                onValueChange={(v) => {
                  setTransactionType(v ?? "");
                  setPage(1);
                }}
                items={[
                  { value: "all", label: "All Types" },
                  ...Object.entries(transactionTypeLabels).map(([value, label]) => ({ value, label })),
                ]}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Types</SelectItem>
                  {Object.entries(transactionTypeLabels).map(([value, label]) => (
                    <SelectItem key={value} value={value}>
                      {label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Date From</Label>
              <Input
                type="date"
                value={dateFrom}
                onChange={(e) => {
                  setDateFrom(e.target.value);
                  setPage(1);
                }}
              />
            </div>
            <div className="space-y-2">
              <Label>Date To</Label>
              <Input
                type="date"
                value={dateTo}
                onChange={(e) => {
                  setDateTo(e.target.value);
                  setPage(1);
                }}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Transactions</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center py-8 text-muted-foreground">
              Loading...
            </div>
          ) : transactions.length === 0 ? (
            <p className="text-sm text-muted-foreground text-center py-8">
              No transactions found.
            </p>
          ) : (
            <>
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
                      <TableHead>Remarks</TableHead>
                      <TableHead>Created By</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {transactions.map((tx: {
                      id: number;
                      transaction_no: string;
                      transaction_type: string;
                      transaction_type_label: string;
                      book?: { title: string } | null;
                      quantity_in: number;
                      quantity_out: number;
                      balance_after: number;
                      transaction_date: string;
                      remarks: string | null;
                      created_by?: { first_name: string; last_name: string } | null;
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
                        <TableCell className="text-sm text-muted-foreground max-w-[200px] truncate">
                          {tx.remarks || "-"}
                        </TableCell>
                        <TableCell className="text-sm">
                          {tx.created_by
                            ? `${tx.created_by.first_name} ${tx.created_by.last_name}`
                            : "-"}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>

              {meta && meta.last_page > 1 && (
                <div className="flex items-center justify-between mt-4">
                  <p className="text-sm text-muted-foreground">
                    Showing {meta.from} to {meta.to} of {meta.total} entries
                  </p>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={page <= 1}
                      onClick={() => setPage(page - 1)}
                    >
                      <ChevronLeft className="size-4" />
                    </Button>
                    <span className="text-sm">
                      Page {page} of {meta.last_page}
                    </span>
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={page >= meta.last_page}
                      onClick={() => setPage(page + 1)}
                    >
                      <ChevronRight className="size-4" />
                    </Button>
                  </div>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

"use client";

import { useState, useEffect, useMemo } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import type { SortingState } from "@tanstack/react-table";
import type { ColumnDef } from "@tanstack/react-table";
import { Plus, Search, Paperclip } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button, buttonVariants } from "@/components/ui/button";
import { DataTable } from "@/components/tables/data-table";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import { ConfirmDialog } from "@/components/dialogs/confirm-dialog";
import { RowActions } from "@/components/common/row-actions";
import { FilterPanel } from "@/components/filters/filter-panel";
import { useExpenseList } from "@/features/expenses/hooks/use-expense-list";
import { useExpenseDelete } from "@/features/expenses/hooks/use-expense-delete";
import { cn } from "@/lib/utils";
import type { Expense } from "@/types/expense";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

const methodVariants: Record<string, "default" | "secondary" | "outline"> = {
  Cash: "default",
  "Bank Transfer": "secondary",
  UPI: "outline",
  Cheque: "outline",
};

export function ExpensesPage() {
  const router = useRouter();
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [page, setPage] = useState(0);
  const [sorting, setSorting] = useState<SortingState>([
    { id: "created_at", desc: true },
  ]);
  const [methodFilter, setMethodFilter] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [deleteId, setDeleteId] = useState<number | null>(null);

  const deleteMutation = useExpenseDelete();

  useEffect(() => {
    const timeout = setTimeout(() => {
      setDebouncedSearch(search);
      setPage(0);
    }, 400);
    return () => clearTimeout(timeout);
  }, [search]);

  const hasActiveFilters = methodFilter !== "" || dateFrom !== "" || dateTo !== "";
  const sort = sorting[0];
  const listParams = useMemo(
    () => ({
      search: debouncedSearch || undefined,
      page: page + 1,
      per_page: 10,
      sort: sort?.id === "created_at" ? undefined : sort?.id,
      direction: sort?.desc ? ("desc" as const) : ("asc" as const),
      payment_method: methodFilter || undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
    }),
    [debouncedSearch, page, sort, methodFilter, dateFrom, dateTo]
  );

  const { data, isLoading } = useExpenseList(listParams);

  const columns: ColumnDef<Expense>[] = useMemo(
    () => [
      {
        id: "expense_no",
        header: "Expense No",
        accessorKey: "expense_no",
        enableSorting: true,
      },
      {
        id: "expense_date",
        header: "Date",
        accessorKey: "expense_date",
        enableSorting: true,
        cell: ({ row }) => fmtDate(row.original.expense_date),
      },
      {
        id: "category",
        header: "Category",
        cell: ({ row }) => row.original.category?.name ?? "-",
      },
      {
        id: "vendor_name",
        header: "Vendor",
        accessorKey: "vendor_name",
        cell: ({ row }) => row.original.vendor_name || "-",
      },
      {
        id: "payment_method",
        header: "Method",
        accessorKey: "payment_method",
        cell: ({ row }) => (
          <Badge variant={methodVariants[row.original.payment_method] ?? "outline"}>
            {row.original.payment_method}
          </Badge>
        ),
      },
      {
        id: "amount",
        header: "Amount",
        accessorKey: "amount",
        enableSorting: true,
        cell: ({ row }) => parseFloat(row.original.amount).toFixed(2),
      },
      {
        id: "attachment",
        header: "",
        accessorKey: "attachment",
        cell: ({ row }) =>
          row.original.attachment ? <Paperclip className="size-3.5 text-muted-foreground" /> : null,
      },
    ],
    []
  );

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Accounts" },
          { label: "Expenses" },
        ]}
      />
      <PageHeader
        title="Expense Management"
        description="Track and manage business expenses."
        actions={
          <Link
            href="/expenses/create"
            className={cn(buttonVariants({ variant: "default" }), "gap-1.5")}
          >
            <Plus className="size-4" />
            New Expense
          </Link>
        }
      />

      <div className="mb-4 flex items-center gap-2">
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search by expense no, vendor..."
            className="pl-8"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
      </div>

      <FilterPanel
        fields={[
          {
            id: "payment_method",
            label: "Method",
            type: "select",
            value: methodFilter,
            onChange: (v) => { setMethodFilter(v); setPage(0); },
            options: [
              { label: "Cash", value: "Cash" },
              { label: "Bank Transfer", value: "Bank Transfer" },
              { label: "UPI", value: "UPI" },
              { label: "Cheque", value: "Cheque" },
            ],
          },
          {
            id: "date_from",
            label: "Date From",
            type: "date",
            value: dateFrom,
            onChange: (v) => { setDateFrom(v); setPage(0); },
          },
          {
            id: "date_to",
            label: "Date To",
            type: "date",
            value: dateTo,
            onChange: (v) => { setDateTo(v); setPage(0); },
          },
        ]}
        onClear={() => { setMethodFilter(""); setDateFrom(""); setDateTo(""); setPage(0); }}
        hasActiveFilters={hasActiveFilters}
      />

      <DataTable
        columns={columns}
        data={data?.data ?? []}
        sorting={sorting}
        onSortingChange={(updater) => {
          setSorting(typeof updater === "function" ? updater(sorting) : updater);
          setPage(0);
        }}
        pageCount={data?.meta?.last_page ?? 1}
        pageIndex={page}
        onPageChange={setPage}
        loading={isLoading}
        actions={(row) => (
          <RowActions
            onView={() => router.push(`/expenses/${row.id}`)}
            onEdit={() => router.push(`/expenses/${row.id}/edit`)}
            onDelete={() => setDeleteId(row.id)}
          />
        )}
      />

      <ConfirmDialog
        open={deleteId !== null}
        onOpenChange={(open) => {
          if (!open) setDeleteId(null);
        }}
        title="Delete Expense?"
        description={
          deleteId
            ? `Expense: ${data?.data?.find((d: Expense) => d.id === deleteId)?.expense_no ?? deleteId}`
            : ""
        }
        confirmLabel="Delete"
        onConfirm={() => {
          if (deleteId) {
            deleteMutation.mutate(deleteId, {
              onSuccess: () => setDeleteId(null),
            });
          }
        }}
        isLoading={deleteMutation.isPending}
        variant="destructive"
      />
    </div>
  );
}

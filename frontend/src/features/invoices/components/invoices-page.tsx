"use client";

import { useState, useEffect, useMemo } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import type { SortingState } from "@tanstack/react-table";
import type { ColumnDef } from "@tanstack/react-table";
import { Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button, buttonVariants } from "@/components/ui/button";
import { DataTable } from "@/components/tables/data-table";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import { ConfirmDialog } from "@/components/dialogs/confirm-dialog";
import { RowActions } from "@/components/common/row-actions";
import { FilterPanel } from "@/components/filters/filter-panel";
import { useInvoiceList } from "@/features/invoices/hooks/use-invoice-list";
import { useInvoiceDelete } from "@/features/invoices/hooks/use-invoice-delete";
import { useInvoiceDownload } from "@/features/invoices/hooks/use-invoice-download";
import { cn } from "@/lib/utils";
import type { Invoice } from "@/types/invoice";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

const statusVariants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
  draft: "default",
  issued: "default",
  partially_paid: "default",
  paid: "default",
  cancelled: "destructive",
};

export function InvoicesPage() {
  const router = useRouter();
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [page, setPage] = useState(0);
  const [sorting, setSorting] = useState<SortingState>([
    { id: "created_at", desc: true },
  ]);
  const [statusFilter, setStatusFilter] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [deleteId, setDeleteId] = useState<number | null>(null);

  const deleteMutation = useInvoiceDelete();
  const downloadMutation = useInvoiceDownload();

  useEffect(() => {
    const timeout = setTimeout(() => {
      setDebouncedSearch(search);
      setPage(0);
    }, 400);
    return () => clearTimeout(timeout);
  }, [search]);

  const hasActiveFilters = statusFilter !== "" || dateFrom !== "" || dateTo !== "";
  const sort = sorting[0];
  const listParams = useMemo(
    () => ({
      search: debouncedSearch || undefined,
      page: page + 1,
      per_page: 10,
      sort: sort?.id === "created_at" ? undefined : sort?.id,
      direction: sort?.desc ? ("desc" as const) : ("asc" as const),
      status: statusFilter || undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
    }),
    [debouncedSearch, page, sort, statusFilter, dateFrom, dateTo]
  );

  const { data, isLoading } = useInvoiceList(listParams);

  const columns: ColumnDef<Invoice>[] = useMemo(
    () => [
      {
        id: "serial",
        header: "Invoice Serial",
        accessorKey: "serial",
        enableSorting: true,
      },
      {
        id: "invoice_date",
        header: "Invoice Date",
        accessorKey: "invoice_date",
        enableSorting: true,
        cell: ({ row }) => fmtDate(row.original.invoice_date),
      },
      {
        id: "due_date",
        header: "Due Date",
        accessorKey: "due_date",
        enableSorting: true,
        cell: ({ row }) => fmtDate(row.original.due_date),
      },
      {
        id: "customer",
        header: "Customer",
        cell: ({ row }) =>
          row.original.customer?.company_name ?? row.original.customer?.name ?? "-",
      },
      {
        id: "grand_total",
        header: "Amount",
        accessorKey: "grand_total",
        enableSorting: true,
        cell: ({ row }) => row.original.grand_total,
      },
      {
        id: "status",
        header: "Status",
        accessorKey: "status",
        enableSorting: true,
        cell: ({ row }) => (
          <Badge
            variant={statusVariants[row.original.status] ?? "default"}
            className={
              row.original.status === "paid"
                ? "bg-emerald-600 hover:bg-emerald-600/80"
                : row.original.status === "issued"
                  ? "bg-blue-600 hover:bg-blue-600/80"
                  : row.original.status === "partially_paid"
                    ? "bg-amber-600 hover:bg-amber-600/80"
                    : undefined
            }
          >
            {row.original.status.replace("_", " ").toUpperCase()}
          </Badge>
        ),
      },
    ],
    []
  );

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Invoices" },
        ]}
      />
      <PageHeader
        title="Invoice Management"
        description="Manage invoices."
        actions={
          <Link
            href="/invoices/create"
            className={cn(buttonVariants({ variant: "default" }), "gap-1.5")}
          >
            <Plus className="size-4" />
            New Invoice
          </Link>
        }
      />

      <div className="mb-4 flex items-center gap-2">
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search by invoice serial, customer..."
            className="pl-8"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
      </div>

      <FilterPanel
        fields={[
          {
            id: "status",
            label: "Status",
            type: "select",
            value: statusFilter,
            onChange: (v) => { setStatusFilter(v); setPage(0); },
            options: [
              { label: "Draft", value: "draft" },
              { label: "Issued", value: "issued" },
              { label: "Partially Paid", value: "partially_paid" },
              { label: "Paid", value: "paid" },
              { label: "Cancelled", value: "cancelled" },
            ],
          },
          {
            id: "date_from",
            label: "Invoice Date From",
            type: "date",
            value: dateFrom,
            onChange: (v) => { setDateFrom(v); setPage(0); },
          },
          {
            id: "date_to",
            label: "Invoice Date To",
            type: "date",
            value: dateTo,
            onChange: (v) => { setDateTo(v); setPage(0); },
          },
        ]}
        onClear={() => { setStatusFilter(""); setDateFrom(""); setDateTo(""); setPage(0); }}
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
            onView={() => router.push(`/invoices/${row.id}`)}
            onEdit={() => router.push(`/invoices/${row.id}/edit`)}
            onDelete={() => setDeleteId(row.id)}
            onDownloadPDF={() => downloadMutation.mutate(row.id)}
            downloadPending={downloadMutation.isPending}
          />
        )}
      />

      <ConfirmDialog
        open={deleteId !== null}
        onOpenChange={(open) => {
          if (!open) setDeleteId(null);
        }}
        title="Delete Invoice?"
        description={
          deleteId
            ? `Invoice: ${data?.data?.find((d: Invoice) => d.id === deleteId)?.serial ?? deleteId}`
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

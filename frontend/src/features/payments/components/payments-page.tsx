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
import { usePaymentList } from "@/features/payments/hooks/use-payment-list";
import { usePaymentDelete } from "@/features/payments/hooks/use-payment-delete";
import { usePaymentDownload } from "@/features/payments/hooks/use-payment-download";
import { cn } from "@/lib/utils";
import type { Payment } from "@/types/payment";

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

export function PaymentsPage() {
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

  const deleteMutation = usePaymentDelete();
  const downloadMutation = usePaymentDownload();

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

  const { data, isLoading } = usePaymentList(listParams);

  const columns: ColumnDef<Payment>[] = useMemo(
    () => [
      {
        id: "payment_no",
        header: "Receipt No",
        accessorKey: "payment_no",
        enableSorting: true,
      },
      {
        id: "payment_date",
        header: "Date",
        accessorKey: "payment_date",
        enableSorting: true,
        cell: ({ row }) => fmtDate(row.original.payment_date),
      },
      {
        id: "customer",
        header: "Customer",
        cell: ({ row }) =>
          row.original.customer?.company_name ?? row.original.customer?.name ?? "-",
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
        id: "payment_status",
        header: "Status",
        accessorKey: "payment_status",
        enableSorting: true,
        cell: ({ row }) => {
          const status = row.original.payment_status;
          const variant: Record<string, string> = {
            Paid: "bg-emerald-600 hover:bg-emerald-600/80",
            "Partially Paid": "bg-amber-600 hover:bg-amber-600/80",
            Unpaid: "bg-red-600 hover:bg-red-600/80",
          };
          return (
            <Badge className={variant[status] ?? ""}>
              {status.toUpperCase()}
            </Badge>
          );
        },
      },
      {
        id: "total_amount",
        header: "Amount",
        accessorKey: "total_amount",
        enableSorting: true,
        cell: ({ row }) => parseFloat(row.original.total_amount).toFixed(2),
      },
    ],
    []
  );

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Payments" },
        ]}
      />
      <PageHeader
        title="Payment Management"
        description="Manage payments received from customers."
        actions={
          <Link
            href="/payments/create"
            className={cn(buttonVariants({ variant: "default" }), "gap-1.5")}
          >
            <Plus className="size-4" />
            New Payment
          </Link>
        }
      />

      <div className="mb-4 flex items-center gap-2">
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search by receipt no, customer..."
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
            onView={() => router.push(`/payments/${row.id}`)}
            onEdit={() => router.push(`/payments/${row.id}/edit`)}
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
        title="Delete Payment?"
        description={
          deleteId
            ? `Payment Receipt: ${data?.data?.find((d: Payment) => d.id === deleteId)?.payment_no ?? deleteId}`
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

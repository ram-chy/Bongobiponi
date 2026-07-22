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
import { useQuotationList } from "@/features/quotations/hooks/use-quotation-list";
import { useQuotationDelete } from "@/features/quotations/hooks/use-quotation-delete";
import { useQuotationDownload } from "@/features/quotations/hooks/use-quotation-download";
import { cn } from "@/lib/utils";
import type { Quotation } from "@/types/quotation";

const statusVariants: Record<string, "default" | "secondary" | "destructive" | "outline" | "ghost" | "link"> = {
  draft: "default",
  sent: "secondary",
  accepted: "default",
  rejected: "destructive",
  expired: "outline",
};

export function QuotationsPage() {
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

  const deleteMutation = useQuotationDelete();
  const downloadMutation = useQuotationDownload();

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

  const { data, isLoading } = useQuotationList(listParams);

  const columns: ColumnDef<Quotation>[] = useMemo(
    () => [
      {
        id: "quotation_serial",
        header: "Quotation Serial",
        accessorKey: "quotation_serial",
        enableSorting: true,
      },
      {
        id: "quotation_date",
        header: "Date",
        accessorKey: "quotation_date",
        enableSorting: true,
        cell: ({ row }) => {
          const d = row.original.quotation_date;
          if (!d) return "-";
          const [y, m, day] = d.split("-");
          const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
          return `${parseInt(day)} ${months[parseInt(m) - 1]} ${y}`;
        },
      },
      {
        id: "company_name",
        header: "Customer Company",
        cell: ({ row }) => row.original.customer?.company_name ?? "-",
      },
      {
        id: "contact_person",
        header: "Contact Person",
        cell: ({ row }) => row.original.customer?.name ?? "-",
      },
      {
        id: "grand_total",
        header: "Grand Total",
        accessorKey: "grand_total",
        enableSorting: true,
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
                row.original.status === "accepted"
                ? "bg-emerald-600 hover:bg-emerald-600/80"
                : undefined
            }
          >
            {row.original.status.toUpperCase()}
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
          { label: "Quotations" },
        ]}
      />
      <PageHeader
        title="Quotation Management"
        description="Manage quotations."
        actions={
          <Link
            href="/quotations/create"
            className={cn(buttonVariants({ variant: "default" }), "gap-1.5")}
          >
            <Plus className="size-4" />
            New Quotation
          </Link>
        }
      />

      <div className="mb-4 flex items-center gap-2">
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search quotations..."
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
              { label: "Sent", value: "sent" },
              { label: "Accepted", value: "accepted" },
              { label: "Rejected", value: "rejected" },
              { label: "Expired", value: "expired" },
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
            onView={() => router.push(`/quotations/${row.id}`)}
            onEdit={() => router.push(`/quotations/${row.id}/edit`)}
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
        title="Delete Quotation?"
        description={
          deleteId
            ? `Quotation: ${data?.data?.find((q: Quotation) => q.id === deleteId)?.quotation_serial ?? deleteId}`
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

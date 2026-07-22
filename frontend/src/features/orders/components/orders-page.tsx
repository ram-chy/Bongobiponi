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
import { useOrderList } from "@/features/orders/hooks/use-order-list";
import { useOrderDelete } from "@/features/orders/hooks/use-order-delete";
import { useOrderDownload } from "@/features/orders/hooks/use-order-download";
import { cn } from "@/lib/utils";
import type { Order } from "@/types/order";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

const statusVariants: Record<string, "default" | "secondary" | "destructive" | "outline" | "ghost" | "link"> = {
  draft: "default",
  confirmed: "secondary",
  cancelled: "destructive",
  completed: "default",
};

export function OrdersPage() {
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

  const deleteMutation = useOrderDelete();
  const downloadMutation = useOrderDownload();

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

  const { data, isLoading } = useOrderList(listParams);

  const columns: ColumnDef<Order>[] = useMemo(
    () => [
      {
        id: "order_serial",
        header: "Order Serial",
        accessorKey: "order_serial",
        enableSorting: true,
      },
      {
        id: "created_at",
        header: "Create Date",
        accessorKey: "created_at",
        enableSorting: true,
        cell: ({ row }) => fmtDate(row.original.created_at),
      },
      {
        id: "order_date",
        header: "Order Date",
        accessorKey: "order_date",
        enableSorting: true,
        cell: ({ row }) => fmtDate(row.original.order_date),
      },
      {
        id: "expected_delivery_date",
        header: "Exp. Delivery",
        accessorKey: "expected_delivery_date",
        cell: ({ row }) => fmtDate(row.original.expected_delivery_date),
      },
      {
        id: "customer",
        header: "Customer",
        cell: ({ row }) => row.original.customer?.company_name ?? row.original.customer?.name ?? "-",
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
              row.original.status === "completed"
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
          { label: "Order Booking" },
        ]}
      />
      <PageHeader
        title="Order Management"
        description="Manage order bookings."
        actions={
          <Link
            href="/orders/create"
            className={cn(buttonVariants({ variant: "default" }), "gap-1.5")}
          >
            <Plus className="size-4" />
            New Order
          </Link>
        }
      />

      <div className="mb-4 flex items-center gap-2">
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search orders..."
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
              { label: "Confirmed", value: "confirmed" },
              { label: "Cancelled", value: "cancelled" },
              { label: "Completed", value: "completed" },
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
            onView={() => router.push(`/orders/${row.id}`)}
            onEdit={() => router.push(`/orders/${row.id}/edit`)}
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
        title="Delete Order?"
        description={
          deleteId
            ? `Order: ${data?.data?.find((o: Order) => o.id === deleteId)?.order_serial ?? deleteId}`
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

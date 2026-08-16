"use client";

import { useState, useEffect, useMemo } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import type { SortingState } from "@tanstack/react-table";
import { Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button, buttonVariants } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { DataTable } from "@/components/tables/data-table";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { ConfirmDialog } from "@/components/dialogs/confirm-dialog";
import { RowActions } from "@/components/common/row-actions";
import { useEntityList } from "@/hooks/use-entity-list";
import { useEntityDelete } from "@/hooks/use-entity-delete";
import { cn } from "@/lib/utils";
import type { EntityConfig } from "@/types/entity";
import type { ColumnDef } from "@tanstack/react-table";

interface EntityPageProps<T> {
  config: EntityConfig<T>;
  breadcrumbItems: { label: string; href?: string }[];
}

export function EntityPage<T extends { id: number }>({
  config,
  breadcrumbItems,
}: EntityPageProps<T>) {
  const router = useRouter();
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [page, setPage] = useState(0);
  const [sorting, setSorting] = useState<SortingState>(
    config.defaultSort
      ? [config.defaultSort]
      : [{ id: "created_at", desc: true }]
  );
  const [deleteId, setDeleteId] = useState<number | null>(null);

  const initialFilters = useMemo(() => {
    const map: Record<string, string> = {};
    for (const f of config.filters ?? []) {
      map[f.key] = "";
    }
    return map;
  }, [config.filters]);

  const [filters, setFilters] = useState<Record<string, string>>(initialFilters);

  useEffect(() => {
    setFilters(initialFilters);
  }, [initialFilters]);

  useEffect(() => {
    const timeout = setTimeout(() => {
      setDebouncedSearch(search);
      setPage(0);
    }, 400);
    return () => clearTimeout(timeout);
  }, [search]);

  const sort = sorting[0];
  const listParams = {
    search: debouncedSearch || undefined,
    page: page + 1,
    per_page: config.perPage ?? 15,
    sort: sort?.id === "created_at" ? undefined : sort?.id,
    direction: sort?.desc ? ("desc" as const) : ("asc" as const),
    ...Object.fromEntries(
      Object.entries(filters).filter(([, v]) => v !== "")
    ),
  };

  const resolveViewRoute = (id: number) =>
    (config.viewRoute ?? config.editRoute).replace(":id", String(id));
  const resolveEditRoute = (id: number) =>
    config.editRoute.replace(":id", String(id));

  const { data, isLoading, isError, error } = useEntityList<T>(config, listParams);
  const deleteMutation = useEntityDelete(config.endpoint, config.endpoint);

  return (
    <div>
      <PageBreadcrumb items={breadcrumbItems} />
      <PageHeader
        title={config.title}
        description={config.description}
        actions={
          <div className="flex items-center gap-2">
            {config.headerActions}
            {config.createRoute && (
              <Link
                href={config.createRoute}
                className={cn(buttonVariants({ variant: "default" }), "gap-1.5")}
              >
                <Plus className="size-4" />
                New
              </Link>
            )}
          </div>
        }
      />

      <div className="mb-4 flex flex-wrap items-center gap-2">
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder={config.searchPlaceholder ?? "Search..."}
            className="pl-8"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        {config.filters?.map((filter) => (
          <Select
            key={filter.key}
            value={filters[filter.key] ?? null}
            onValueChange={(value) => {
              setFilters((prev) => ({ ...prev, [filter.key]: value ?? "" }));
              setPage(0);
            }}
          >
            <SelectTrigger className="w-[150px]">
              <SelectValue placeholder={filter.label}>
                {filter.options.find((o) => o.value === (filters[filter.key] ?? null))?.label ?? null}
              </SelectValue>
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">All {filter.label}</SelectItem>
              {filter.options.map((opt) => (
                <SelectItem key={opt.value} value={opt.value}>
                  {opt.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        ))}
      </div>

      {isError && (
        <div className="rounded-md border border-destructive/50 bg-destructive/10 p-4 text-sm text-destructive">
          <p className="font-medium">Failed to load data</p>
          <p className="mt-1">{error instanceof Error ? error.message : "An unexpected error occurred."}</p>
        </div>
      )}

      <DataTable
        columns={useMemo(() => config.columns.map((col) => {
          const tableCol: ColumnDef<T> = {
            id: col.id,
            header: col.header,
            accessorKey: col.accessorKey as string,
            enableSorting: col.sortable !== false,
          };
          if (col.cell) {
            tableCol.cell = (info: { row: { original: Record<string, unknown> } }) =>
              (col.cell as (row: Record<string, unknown>) => React.ReactNode)(info.row.original);
          }
          return tableCol;
        }), [config.columns])}
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
            onView={() => router.push(resolveViewRoute(row.id))}
            onEdit={() => router.push(resolveEditRoute(row.id))}
            onDelete={() => setDeleteId(row.id)}
          />
        )}
      />

      <ConfirmDialog
        open={deleteId !== null}
        onOpenChange={(open) => {
          if (!open) setDeleteId(null);
        }}
        title="Delete Record"
        description="Are you sure you want to delete this record? This action cannot be undone."
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

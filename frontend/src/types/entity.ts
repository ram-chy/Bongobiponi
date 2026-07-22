import type { ColumnSort, Row } from "@tanstack/react-table";
import type { ReactNode } from "react";

export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
}

export interface ListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
  [key: string]: unknown;
}

export interface ColumnDef<T> {
  id: string;
  header: string;
  accessorKey?: keyof T | string;
  cell?: (row: T) => ReactNode;
  sortable?: boolean;
  hideable?: boolean;
}

export interface EntityConfig<T> {
  title: string;
  description?: string;
  endpoint: string;
  createRoute?: string;
  viewRoute?: string;
  editRoute: string;
  columns: ColumnDef<T>[];
  searchPlaceholder?: string;
  defaultSort?: ColumnSort;
  perPage?: number;
}

export interface RowAction<T> {
  label: string;
  icon?: ReactNode;
  onClick: (row: T) => void;
  variant?: "default" | "destructive";
}

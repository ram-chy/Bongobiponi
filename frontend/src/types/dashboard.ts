export interface DashboardSummary {
  total_orders: number;
  pending_orders: number;
  total_books: number;
  low_stock_count: number;
  sales_value: number;
  sales_value_this_month: number;
  purchase_value: number;
  purchase_value_this_month: number;
  expense_total: number;
  expense_total_this_month: number;
  profit: number;
  net_profit: number;
  profit_this_month: number;
  net_profit_this_month: number;
}

export interface DashboardMonthlyEntry {
  month: string;
  label: string;
  sales_value: number;
  profit: number;
}

export interface DashboardTopBook {
  book_id: number;
  title: string;
  quantity: number;
  sales_value: number;
}

export interface DashboardRecentOrder {
  id: number;
  order_serial: string;
  customer: string | null;
  grand_total: number;
  status: string | null;
  order_date: string | null;
}

export interface DashboardData {
  summary: DashboardSummary;
  monthly: DashboardMonthlyEntry[];
  top_books: DashboardTopBook[];
  recent_orders: DashboardRecentOrder[];
}

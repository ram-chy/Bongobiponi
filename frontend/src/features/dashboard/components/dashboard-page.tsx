"use client";

import Link from "next/link";
import { Loader2 } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { OrderStatusBadge } from "@/features/orders/components/order-status-badge";
import { useDashboardSummary } from "@/features/dashboard/hooks/use-dashboard-summary";
import type { OrderStatus } from "@/types/order";

const currency = new Intl.NumberFormat("en-IN", {
  style: "currency",
  currency: "INR",
  maximumFractionDigits: 0,
});

function StatCard({
  label,
  value,
  sub,
}: {
  label: string;
  value: string;
  sub?: string;
}) {
  return (
    <Card>
      <CardContent className="pt-6">
        <p className="text-sm text-muted-foreground">{label}</p>
        <p className="mt-1 text-2xl font-bold tracking-tight">{value}</p>
        {sub && <p className="mt-1 text-xs text-muted-foreground">{sub}</p>}
      </CardContent>
    </Card>
  );
}

function fmtDate(value: string | null | undefined): string {
  if (!value) return "-";
  const [y, m, d] = value.split("-");
  const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  return `${parseInt(d)} ${months[parseInt(m) - 1]} ${y}`;
}

export function DashboardPage() {
  const { data, isLoading, isError } = useDashboardSummary();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (isError || !data) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Failed to load dashboard data.
      </div>
    );
  }

  const { summary, monthly, top_books, recent_orders } = data;

  const statCards = [
    {
      label: "Total Sales Value",
      value: currency.format(summary.sales_value),
      sub: `${currency.format(summary.sales_value_this_month)} this month`,
    },
    {
      label: "Profit",
      value: currency.format(summary.profit),
      sub: `${currency.format(summary.profit_this_month)} this month`,
    },
    {
      label: "Net Profit",
      value: currency.format(summary.net_profit),
      sub: `After ${currency.format(summary.expense_total)} expenses`,
    },
    {
      label: "Orders",
      value: String(summary.total_orders),
      sub: `${summary.pending_orders} pending`,
    },
    {
      label: "Purchases",
      value: currency.format(summary.purchase_value),
      sub: `${currency.format(summary.purchase_value_this_month)} this month`,
    },
    {
      label: "Expenses",
      value: currency.format(summary.expense_total),
      sub: `${currency.format(summary.expense_total_this_month)} this month`,
    },
    {
      label: "Books",
      value: String(summary.total_books),
      sub: `${summary.low_stock_count} low on stock`,
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
        <p className="text-muted-foreground">Sales values and profits overview.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {statCards.map((card) => (
          <StatCard
            key={card.label}
            label={card.label}
            value={card.value}
            sub={card.sub}
          />
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Recent Orders</CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b bg-muted/50">
                    <th className="p-2 text-left font-medium">Order</th>
                    <th className="p-2 text-left font-medium">Customer</th>
                    <th className="p-2 text-left font-medium">Date</th>
                    <th className="p-2 text-right font-medium">Total</th>
                    <th className="p-2 text-left font-medium">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {recent_orders.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="p-4 text-center text-muted-foreground">
                        No orders yet.
                      </td>
                    </tr>
                  ) : (
                    recent_orders.map((order) => (
                      <tr key={order.id} className="border-b last:border-0">
                        <td className="p-2">
                          <Link
                            href={`/orders/${order.id}`}
                            className="font-medium text-primary hover:underline"
                          >
                            {order.order_serial}
                          </Link>
                        </td>
                        <td className="p-2">{order.customer ?? "-"}</td>
                        <td className="p-2">{fmtDate(order.order_date)}</td>
                        <td className="p-2 text-right font-medium">
                          {currency.format(order.grand_total)}
                        </td>
                        <td className="p-2">
                          <OrderStatusBadge status={order.status as OrderStatus | null} />
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Top Selling Books</CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b bg-muted/50">
                    <th className="p-2 text-left font-medium">Book</th>
                    <th className="p-2 text-right font-medium">Qty</th>
                    <th className="p-2 text-right font-medium">Sales Value</th>
                  </tr>
                </thead>
                <tbody>
                  {top_books.length === 0 ? (
                    <tr>
                      <td colSpan={3} className="p-4 text-center text-muted-foreground">
                        No sales recorded yet.
                      </td>
                    </tr>
                  ) : (
                    top_books.map((book) => (
                      <tr key={book.book_id} className="border-b last:border-0">
                        <td className="p-2 font-medium">
                          <Link
                            href={`/books/${book.book_id}`}
                            className="hover:underline"
                          >
                            {book.title}
                          </Link>
                        </td>
                        <td className="p-2 text-right">{book.quantity}</td>
                        <td className="p-2 text-right">
                          {currency.format(book.sales_value)}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Monthly Sales & Profit</CardTitle>
        </CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-muted/50">
                  <th className="p-2 text-left font-medium">Month</th>
                  <th className="p-2 text-right font-medium">Sales Value</th>
                  <th className="p-2 text-right font-medium">Profit</th>
                </tr>
              </thead>
              <tbody>
                {monthly.map((entry) => (
                  <tr key={entry.month} className="border-b last:border-0">
                    <td className="p-2 font-medium">{entry.label}</td>
                    <td className="p-2 text-right">
                      {currency.format(entry.sales_value)}
                    </td>
                    <td className="p-2 text-right">
                      {currency.format(entry.profit)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

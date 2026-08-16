<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        // Data visibility is enforced by the CreatedByScope on Order, Purchase
        // and Expense: regular users only ever aggregate their own records.
        $monthStart = now()->startOfMonth();
        $excluded = [OrderStatus::Cancelled->value, OrderStatus::Rto->value];

        $orders = Order::query()
            ->with(['items.book', 'customer'])
            ->whereNotIn('status', $excluded)
            ->get();

        $ordersThisMonth = $orders->filter(fn (Order $order) => $order->order_date?->gte($monthStart));

        [$salesValue, $cogs] = $this->salesAndCost($orders);
        [$monthSales, $monthCogs] = $this->salesAndCost($ordersThisMonth);

        $expenseTotal = (float) Expense::sum('amount');
        $expenseThisMonth = (float) Expense::where('expense_date', '>=', $monthStart)->sum('amount');

        $purchaseValue = $this->purchaseValue();
        $purchaseThisMonth = $this->purchaseValue($monthStart);

        $profit = $salesValue - $cogs;
        $monthProfit = $monthSales - $monthCogs;

        return $this->successResponse([
            'summary' => [
                'total_orders' => Order::count(),
                'pending_orders' => Order::whereIn('status', [
                    OrderStatus::Intake->value,
                    OrderStatus::ToProcure->value,
                    OrderStatus::ToPack->value,
                ])->count(),
                'total_books' => Book::count(),
                'low_stock_count' => $this->lowStockCount(),
                'sales_value' => round($salesValue, 2),
                'sales_value_this_month' => round($monthSales, 2),
                'purchase_value' => round($purchaseValue, 2),
                'purchase_value_this_month' => round($purchaseThisMonth, 2),
                'expense_total' => round($expenseTotal, 2),
                'expense_total_this_month' => round($expenseThisMonth, 2),
                'profit' => round($profit, 2),
                'net_profit' => round($profit - $expenseTotal, 2),
                'profit_this_month' => round($monthProfit, 2),
                'net_profit_this_month' => round($monthProfit - $expenseThisMonth, 2),
            ],
            'monthly' => $this->monthlyTrend($orders, 6),
            'top_books' => $this->topBooks($orders),
            'recent_orders' => $this->recentOrders(),
        ], 'Dashboard summary retrieved successfully');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array{0: float, 1: float}
     */
    private function salesAndCost($orders): array
    {
        $sales = 0.0;
        $cost = 0.0;

        foreach ($orders as $order) {
            $sales += (float) $order->grand_total;

            foreach ($order->items as $item) {
                $cost += (float) $item->ordered_quantity * (float) ($item->book?->purchase_price ?? 0);
            }
        }

        return [$sales, $cost];
    }

    private function purchaseValue(?\Illuminate\Support\Carbon $from = null): float
    {
        $query = Purchase::query()->where('status', 'confirmed')->with('items');

        if ($from !== null) {
            $query->where('purchase_date', '>=', $from);
        }

        return (float) $query->get()->sum(fn (Purchase $purchase) => $purchase->items->sum('total'));
    }

    private function lowStockCount(): int
    {
        return Book::query()
            ->with('stock')
            ->get()
            ->filter(fn (Book $book) => $book->stock !== null
                && (int) $book->stock->current_quantity <= (int) $book->minimum_stock)
            ->count();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array<int, array<string, mixed>>
     */
    private function monthlyTrend($orders, int $months): array
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = (clone $start)->addMonth();

            $monthOrders = $orders->filter(fn (Order $order) => $order->order_date
                && $order->order_date->gte($start)
                && $order->order_date->lt($end));

            [$sales, $cogs] = $this->salesAndCost($monthOrders);

            $trend[] = [
                'month' => $start->format('Y-m'),
                'label' => $start->format('M Y'),
                'sales_value' => round($sales, 2),
                'profit' => round($sales - $cogs, 2),
            ];
        }

        return $trend;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array<int, array<string, mixed>>
     */
    private function topBooks($orders): array
    {
        $aggregate = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (! $item->book_id) {
                    continue;
                }

                if (! isset($aggregate[$item->book_id])) {
                    $aggregate[$item->book_id] = [
                        'book_id' => $item->book_id,
                        'title' => $item->book?->title ?? $item->description,
                        'quantity' => 0,
                        'sales_value' => 0.0,
                    ];
                }

                $aggregate[$item->book_id]['quantity'] += (float) $item->ordered_quantity;
                $aggregate[$item->book_id]['sales_value'] += (float) $item->line_total;
            }
        }

        usort($aggregate, fn (array $a, array $b) => $b['quantity'] <=> $a['quantity']);

        return array_map(fn (array $entry): array => [
            'book_id' => $entry['book_id'],
            'title' => $entry['title'],
            'quantity' => round($entry['quantity'], 2),
            'sales_value' => round($entry['sales_value'], 2),
        ], array_slice($aggregate, 0, 5));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentOrders(): array
    {
        return Order::query()
            ->with('customer')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'order_serial' => $order->order_serial,
                'customer' => $order->customer?->name ?? $order->customer?->company_name ?? null,
                'grand_total' => (float) $order->grand_total,
                'status' => $order->status?->value,
                'order_date' => $order->order_date?->format('Y-m-d'),
            ])
            ->all();
    }
}

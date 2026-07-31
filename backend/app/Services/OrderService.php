<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly OrderSerialGeneratorService $serialGenerator,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Order::query()->with(['customer', 'creator']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function store(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $data['order_serial'] = $this->serialGenerator->generate();
            $data['order_source'] = 'manual';

            $preparedItems = $this->prepareItems($items);
            $calculated = $this->calculateTotals($preparedItems);
            $data['subtotal'] = $calculated['subtotal'];
            $data['discount_amount'] = $calculated['discount_amount'];
            $data['tax_amount'] = $calculated['tax_amount'];
            $data['grand_total'] = $calculated['grand_total'];

            /** @var Order $order */
            $order = Order::create($data);

            $this->syncItems($order, $preparedItems);

            return $order->load(['customer', 'creator', 'items']);
        });
    }

    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);

            if ($items !== null) {
                $data['order_source'] = 'manual';
                $preparedItems = $this->prepareItems($items);
                $calculated = $this->calculateTotals($preparedItems);
                $data['subtotal'] = $calculated['subtotal'];
                $data['discount_amount'] = $calculated['discount_amount'];
                $data['tax_amount'] = $calculated['tax_amount'];
                $data['grand_total'] = $calculated['grand_total'];
            }

            $order->update($data);

            if ($items !== null) {
                $order->items()->delete();
                $this->syncItems($order, $preparedItems);
            }

            return $order->load(['customer', 'creator', 'items']);
        });
    }

    public function findTrashed(int $id): Order
    {
        return Order::withoutGlobalScopes()->withTrashed()->findOrFail($id);
    }

    public function restore(Order $order): Order
    {
        $order->restore();
        return $order->load(['customer', 'creator', 'items']);
    }

    private function prepareItems(array $items): array
    {
        $prepared = [];

        foreach ($items as $index => $item) {
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $orderedQuantity = (float) $item['ordered_quantity'];

            $prepared[] = [
                'quotation_id' => null,
                'quotation_item_id' => null,
                'source_type' => 'manual',
                'item_no' => $index + 1,
                'description' => $item['description'] ?? '',
                'unit' => $item['unit'] ?? '',
                'ordered_quantity' => $orderedQuantity,
                'remaining_order_quantity' => $orderedQuantity,
                'unit_price' => $unitPrice,
                'price_snapshot' => null,
                'discount_percentage' => (float) ($item['discount_percentage'] ?? 0),
                'discount_amount' => 0,
                'discount_snapshot' => null,
                'tax_percentage' => (float) ($item['tax_percentage'] ?? 0),
                'tax_amount' => 0,
                'tax_snapshot' => null,
                'line_total' => 0,
                'remarks' => $item['remarks'] ?? null,
                'sort_order' => $index + 1,
            ];
        }

        return $prepared;
    }

    private function calculateTotals(array &$preparedItems): array
    {
        $subtotal = 0;
        $totalDiscount = 0;
        $totalTax = 0;

        foreach ($preparedItems as &$item) {
            $quantity = (float) $item['ordered_quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discountPercentage = (float) ($item['discount_percentage'] ?? 0);
            $taxPercentage = (float) ($item['tax_percentage'] ?? 0);

            $baseAmount = $quantity * $unitPrice;
            $discountAmount = $baseAmount * ($discountPercentage / 100);
            $taxableAmount = $baseAmount - $discountAmount;
            $taxAmount = $taxableAmount * ($taxPercentage / 100);
            $lineTotal = $baseAmount - $discountAmount + $taxAmount;

            $item['discount_amount'] = round($discountAmount, 2);
            $item['tax_amount'] = round($taxAmount, 2);
            $item['line_total'] = round($lineTotal, 2);

            $subtotal += $baseAmount;
            $totalDiscount += $discountAmount;
            $totalTax += $taxAmount;
        }
        unset($item);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($totalDiscount, 2),
            'tax_amount' => round($totalTax, 2),
            'grand_total' => round($subtotal - $totalDiscount + $totalTax, 2),
        ];
    }

    public function recalculateStatus(Order $order): void
    {
        $hasRemaining = $order->items()->where('remaining_order_quantity', '>', 0)->exists();

        if ($hasRemaining && $order->status === 'completed') {
            $order->update(['status' => 'draft']);
        } elseif (! $hasRemaining && $order->status !== 'completed') {
            $order->update(['status' => 'completed']);
        }
    }

    private function syncItems(Order $order, array $preparedItems): void
    {
        foreach ($preparedItems as $item) {
            $order->items()->create($item);
        }
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('order_serial', 'like', "%{$search}%")
              ->orWhere('status', 'like', "%{$search}%")
              ->orWhereHas('customer', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
              });
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['order_source'])) {
            $query->where('order_source', $filters['order_source']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['order_serial'])) {
            $query->where('order_serial', 'like', "%{$filters['order_serial']}%");
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('order_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('order_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['order_serial', 'order_date', 'created_at', 'grand_total', 'status', 'expected_delivery_date'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}

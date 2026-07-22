<?php

namespace App\Services;

use App\Models\DeliveryChallan;
use App\Models\Order;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DeliveryChallanService
{
    public function __construct(
        private readonly DeliveryChallanSerialGeneratorService $serialGenerator,
        private readonly DocumentReferenceService $documentReferenceService,
        private readonly DeliveryChallanStatusTransitionService $statusTransitionService,
        private readonly SalesOrderService $salesOrderService,
        private readonly OrderService $orderService,
        private readonly ActivityLogService $activityLogService,
        private readonly SourceDocumentOwnershipService $sourceDocumentOwnership,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = DeliveryChallan::query()->with(['customer', 'creator', 'items']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 10), 100));
    }

    public function store(array $data): DeliveryChallan
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $data['serial'] = $this->serialGenerator->generate();

            $preparedItems = $this->prepareItems($items, $data['customer_id']);
            $calculated = $this->calculateTotals($preparedItems);
            $data['subtotal'] = $calculated['subtotal'];
            $data['discount_amount'] = $calculated['discount_amount'];
            $data['tax_amount'] = $calculated['tax_amount'];
            $data['grand_total'] = $calculated['grand_total'];

            /** @var DeliveryChallan $deliveryChallan */
            $deliveryChallan = DeliveryChallan::create($data);

            $this->syncItems($deliveryChallan, $preparedItems);

            $this->activityLogService->logCreate('delivery_challan', 'delivery_challan', $deliveryChallan->id, $data);

            $this->autoCompleteAffectedSalesOrders($deliveryChallan);
            $this->autoCompleteAffectedOrders($deliveryChallan);

            return $deliveryChallan->load([
                'customer',
                'creator',
                'items.salesOrderItem',
                'items.salesOrder',
                'items.orderBooking',
                'items.quotation',
            ]);
        });
    }

    public function update(DeliveryChallan $deliveryChallan, array $data): DeliveryChallan
    {
        return DB::transaction(function () use ($deliveryChallan, $data) {
            $originalStatus = $deliveryChallan->status;

            $items = $data['items'] ?? null;
            unset($data['items']);

            $newStatus = $data['status'] ?? null;
            unset($data['status']);

            if ($newStatus !== null && $newStatus !== $originalStatus) {
                $this->statusTransitionService->validateTransition($originalStatus, $newStatus);
                $deliveryChallan->update(['status' => $newStatus, 'updated_by' => auth()->id()]);
                $this->activityLogService->logAction('delivery_challan', 'delivery_challan', $deliveryChallan->id, "status_changed:{$originalStatus}->{$newStatus}");
            }

            if (! empty($data)) {
                $data['updated_by'] = auth()->id();
                $oldData = $deliveryChallan->only(array_keys($data));
                $deliveryChallan->update($data);
                $this->activityLogService->logUpdate('delivery_challan', 'delivery_challan', $deliveryChallan->id, $oldData, $data);
            }

            if ($items !== null) {
                $this->restoreSalesOrderQuantities($deliveryChallan);

                $preparedItems = $this->prepareItems($items, $data['customer_id'] ?? $deliveryChallan->customer_id);
                $calculated = $this->calculateTotals($preparedItems);
                $deliveryChallan->update([
                    'subtotal' => $calculated['subtotal'],
                    'discount_amount' => $calculated['discount_amount'],
                    'tax_amount' => $calculated['tax_amount'],
                    'grand_total' => $calculated['grand_total'],
                    'updated_by' => auth()->id(),
                ]);

                $deliveryChallan->items()->delete();
                $this->syncItems($deliveryChallan, $preparedItems);
            }

            $this->autoCompleteAffectedSalesOrders($deliveryChallan);
            $this->autoCompleteAffectedOrders($deliveryChallan);

            return $deliveryChallan->load([
                'customer',
                'creator',
                'items.salesOrderItem',
                'items.salesOrder',
                'items.orderBooking',
                'items.quotation',
            ]);
        });
    }

    public function findTrashed(int $id): DeliveryChallan
    {
        return DeliveryChallan::withoutGlobalScopes()->withTrashed()->findOrFail($id);
    }

    public function restore(DeliveryChallan $deliveryChallan): DeliveryChallan
    {
        $deliveryChallan->restore();

        $this->activityLogService->logRestore('delivery_challan', 'delivery_challan', $deliveryChallan->id);

        return $deliveryChallan->load([
            'customer',
            'creator',
            'items.salesOrderItem',
            'items.salesOrder',
            'items.orderBooking',
            'items.quotation',
        ]);
    }

    public function getRemainingItems(int $salesOrderId): SalesOrder
    {
        $salesOrder = SalesOrder::withoutGlobalScopes()->withTrashed()->findOrFail($salesOrderId);

        $user = auth()->user();
        if ($user->hasRole('regular_user') && $salesOrder->created_by !== $user->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You do not have permission to access this sales order.');
        }

        return $salesOrder->load(['items' => function ($query) {
            $query->where('remaining_sales_quantity', '>', 0);
        }]);
    }

    private function prepareItems(array $items, int $customerId): array
    {
        $prepared = [];

        foreach ($items as $index => $item) {
            $deliveredQuantity = (float) $item['delivered_quantity'];
            $salesOrderItem = $this->sourceDocumentOwnership->salesOrderItem($item['sales_order_item_id']);
            $this->sourceDocumentOwnership->ensureMatchesCustomer($customerId, $salesOrderItem->salesOrder);
            $salesOrderItem->loadMissing(['salesOrder', 'order', 'orderItem.quotationItem']);

            $lockedSalesOrderItem = SalesOrderItem::lockForUpdate()->findOrFail($salesOrderItem->id);
            $availableQuantity = (float) $lockedSalesOrderItem->remaining_sales_quantity;
            if ($deliveredQuantity > $availableQuantity) {
                throw new \InvalidArgumentException(
                    "Delivery quantity ({$deliveredQuantity}) exceeds remaining quantity ({$availableQuantity}) for sales order item #{$salesOrderItem->id}."
                );
            }

            $prepared[] = [
                'sales_order_id' => $salesOrderItem->sales_order_id,
                'sales_order_item_id' => $salesOrderItem->id,
                'order_booking_id' => $salesOrderItem->order_id,
                'order_booking_item_id' => $salesOrderItem->order_item_id,
                'quotation_id' => $salesOrderItem->orderItem?->quotation_item_id
                    ? optional($salesOrderItem->orderItem->quotationItem)->quotation_id
                    : null,
                'quotation_item_id' => $salesOrderItem->orderItem?->quotation_item_id,
                'item_description' => $salesOrderItem->description,
                'unit' => $salesOrderItem->unit,
                'ordered_quantity' => $salesOrderItem->ordered_quantity,
                'delivered_quantity' => $deliveredQuantity,
                'unit_price' => $salesOrderItem->unit_price,
                'remarks' => $item['remarks'] ?? null,
            ];
        }

        return $prepared;
    }

    private function calculateTotals(array &$preparedItems): array
    {
        $subtotal = 0;

        foreach ($preparedItems as &$item) {
            $quantity = (float) $item['delivered_quantity'];
            $unitPrice = (float) $item['unit_price'];
            $lineTotal = $quantity * $unitPrice;
            $subtotal += $lineTotal;
        }
        unset($item);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => round($subtotal, 2),
        ];
    }

    private function autoCompleteAffectedSalesOrders(DeliveryChallan $deliveryChallan): void
    {
        $deliveryChallan->load('items');
        $salesOrderIds = $deliveryChallan->items->pluck('sales_order_id')->unique();

        foreach ($salesOrderIds as $salesOrderId) {
            $salesOrder = SalesOrder::find($salesOrderId);
            if ($salesOrder) {
                $this->salesOrderService->autoCompleteIfAllDelivered($salesOrder);
            }
        }
    }

    private function autoCompleteAffectedOrders(DeliveryChallan $deliveryChallan): void
    {
        $deliveryChallan->load('items');
        $salesOrderIds = $deliveryChallan->items->pluck('sales_order_id')->unique()->filter();
        $orderBookingIds = $deliveryChallan->items->pluck('order_booking_id')->unique()->filter();

        foreach ($orderBookingIds as $orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $this->orderService->autoCompleteIfAllDelivered($order, $salesOrderIds);
            }
        }
    }

    private function syncItems(DeliveryChallan $deliveryChallan, array $preparedItems): void
    {
        foreach ($preparedItems as $item) {
            $dcItem = $deliveryChallan->items()->create($item);

            $dcItem->remaining_invoice_quantity = $item['delivered_quantity'];
            $dcItem->save();

            $lockedSalesOrderItem = SalesOrderItem::lockForUpdate()->findOrFail($item['sales_order_item_id']);
            $lockedSalesOrderItem->decrement('remaining_sales_quantity', $item['delivered_quantity']);

            $lockedSalesOrderItem->conversions()->create([
                'module' => 'delivery_challan',
                'reference_id' => $dcItem->id,
                'quantity' => $item['delivered_quantity'],
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function restoreSalesOrderQuantities(DeliveryChallan $deliveryChallan): void
    {
        foreach ($deliveryChallan->items as $dcItem) {
            $salesOrderItem = SalesOrderItem::lockForUpdate()->find($dcItem->sales_order_item_id);
            if ($salesOrderItem) {
                $salesOrderItem->increment('remaining_sales_quantity', (float) $dcItem->delivered_quantity);
            }

            $dcItem->salesOrderItem?->conversions()
                ->where('module', 'delivery_challan')
                ->where('reference_id', $dcItem->id)
                ->delete();
        }
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('serial', 'like', "%{$search}%")
              ->orWhere('vehicle_number', 'like', "%{$search}%")
              ->orWhereHas('customer', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
              })
              ->orWhereHas('items', function (Builder $q) use ($search) {
                  $q->whereHas('salesOrder', function (Builder $q) use ($search) {
                      $q->where('sales_order_serial', 'like', "%{$search}%");
                  });
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

        if (! empty($filters['date_from'])) {
            $query->whereDate('delivery_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('delivery_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['serial', 'delivery_date', 'created_at', 'grand_total', 'status'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}

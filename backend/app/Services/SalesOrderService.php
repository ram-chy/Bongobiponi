<?php

namespace App\Services;

use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(
        private readonly SalesOrderSerialGeneratorService $serialGenerator,
        private readonly DocumentReferenceService $documentReferenceService,
        private readonly StatusTransitionService $statusTransitionService,
        private readonly ActivityLogService $activityLogService,
        private readonly SourceDocumentOwnershipService $sourceDocumentOwnership,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = SalesOrder::query()->with(['customer', 'creator']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function store(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $data['sales_order_serial'] = $this->serialGenerator->generate();
            $data['document_reference_uuid'] = $this->documentReferenceService->generateUuid();

            $preparedItems = $this->prepareItems($items, $data['customer_id']);
            $data['sales_order_source'] = $this->determineSource($preparedItems);

            $calculated = $this->calculateTotals($preparedItems);
            $data['subtotal'] = $calculated['subtotal'];
            $data['discount_amount'] = $calculated['discount_amount'];
            $data['tax_amount'] = $calculated['tax_amount'];
            $data['grand_total'] = $calculated['grand_total'];

            /** @var SalesOrder $salesOrder */
            $salesOrder = SalesOrder::create($data);

            $this->syncItems($salesOrder, $preparedItems, $data['document_reference_uuid']);

            $this->activityLogService->logCreate('sales_order', 'sales_order', $salesOrder->id, $data);

            return $salesOrder->load(['customer', 'creator', 'items']);
        });
    }

    public function update(SalesOrder $salesOrder, array $data): SalesOrder
    {
        return DB::transaction(function () use ($salesOrder, $data) {
            $originalStatus = $salesOrder->status;

            if (isset($data['status']) && $data['status'] !== $originalStatus) {
                $salesOrder = $this->statusTransitionService->transition($salesOrder, $data['status']);
                $this->activityLogService->logAction('sales_order', 'sales_order', $salesOrder->id, "status_changed:{$originalStatus}->{$data['status']}");
            }

            $items = $data['items'] ?? null;
            unset($data['items']);
            unset($data['status']);

            if (! empty($data)) {
                $oldData = $salesOrder->only(array_keys($data));
                $salesOrder->update($data);
                $this->activityLogService->logUpdate('sales_order', 'sales_order', $salesOrder->id, $oldData, $data);
            }

            if ($items !== null) {
                $this->ensureNoDownstreamReferences($salesOrder);

                $preparedItems = $this->prepareItems($items, $data['customer_id'] ?? $salesOrder->customer_id);
                $data['sales_order_source'] = $this->determineSource($preparedItems);

                $calculated = $this->calculateTotals($preparedItems);
                $data['subtotal'] = $calculated['subtotal'];
                $data['discount_amount'] = $calculated['discount_amount'];
                $data['tax_amount'] = $calculated['tax_amount'];
                $data['grand_total'] = $calculated['grand_total'];

                $salesOrder->update($data);
                $salesOrder->items()->delete();
                $this->syncItems($salesOrder, $preparedItems, $salesOrder->document_reference_uuid);
            }

            return $salesOrder->load(['customer', 'creator', 'items']);
        });
    }

    private function ensureNoDownstreamReferences(SalesOrder $salesOrder): void
    {
        $hasDownstream = \App\Models\DeliveryChallanItem::where('sales_order_id', $salesOrder->id)->exists();
        if ($hasDownstream) {
            throw new \InvalidArgumentException(
                'Cannot modify sales order items. This sales order has associated Delivery Challans. '
                . 'Please delete the related Delivery Challans first before modifying the sales order items.'
            );
        }
    }

    public function findTrashed(int $id): SalesOrder
    {
        return SalesOrder::withoutGlobalScopes()->withTrashed()->findOrFail($id);
    }

    public function restore(SalesOrder $salesOrder): SalesOrder
    {
        $salesOrder->restore();

        $this->activityLogService->logRestore('sales_order', 'sales_order', $salesOrder->id);

        return $salesOrder->load(['customer', 'creator', 'items']);
    }

    private function determineSource(array $preparedItems): string
    {
        $orderIds = collect($preparedItems)->pluck('order_id')->unique();

        if ($orderIds->count() === 1) {
            return 'single';
        }

        return 'merged';
    }

    private function prepareItems(array $items, int $customerId): array
    {
        $prepared = [];

        foreach ($items as $index => $item) {
            $salesOrderQuantity = (float) $item['sales_order_quantity'];
            $orderItem = $this->sourceDocumentOwnership->orderItem($item['order_item_id']);
            $this->sourceDocumentOwnership->ensureMatchesCustomer($customerId, $orderItem->order);

            $lockedOrderItem = \App\Models\OrderItem::lockForUpdate()->findOrFail($orderItem->id);
            $availableQuantity = (float) $lockedOrderItem->remaining_order_quantity;
            if ($salesOrderQuantity > $availableQuantity) {
                throw new \InvalidArgumentException(
                    "Sales order quantity ({$salesOrderQuantity}) exceeds available quantity ({$availableQuantity}) for order item #{$orderItem->id}."
                );
            }

            $prepared[] = [
                'order_id' => $orderItem->order_id,
                'order_item_id' => $orderItem->id,
                'source_type' => 'order',
                'item_no' => $index + 1,
                'description' => $orderItem->description,
                'unit' => $orderItem->unit,
                'ordered_quantity' => $orderItem->ordered_quantity,
                'sales_order_quantity' => $salesOrderQuantity,
                'remaining_sales_quantity' => $salesOrderQuantity,
                'unit_price' => $orderItem->unit_price,
                'price_snapshot' => json_encode([
                    'unit_price' => $orderItem->unit_price,
                    'description' => $orderItem->description,
                    'unit' => $orderItem->unit,
                ]),
                'discount_percentage' => $orderItem->discount_percentage,
                'discount_amount' => 0,
                'discount_snapshot' => json_encode([
                    'discount_percentage' => $orderItem->discount_percentage,
                    'discount_amount' => $orderItem->discount_amount,
                ]),
                'tax_percentage' => $orderItem->tax_percentage,
                'tax_amount' => 0,
                'tax_snapshot' => json_encode([
                    'tax_percentage' => $orderItem->tax_percentage,
                    'tax_amount' => $orderItem->tax_amount,
                ]),
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
        $grandTotal = 0;

        foreach ($preparedItems as &$item) {
            $quantity = (float) $item['sales_order_quantity'];
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
            $grandTotal += $lineTotal;
        }
        unset($item);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($totalDiscount, 2),
            'tax_amount' => round($totalTax, 2),
            'grand_total' => round($grandTotal, 2),
        ];
    }

    private function syncItems(SalesOrder $salesOrder, array $preparedItems, string $documentUuid): void
    {
        $processedOrders = [];

        foreach ($preparedItems as $item) {
            $lockedOrderItem = \App\Models\OrderItem::lockForUpdate()->findOrFail($item['order_item_id']);
            $lockedOrderItem->decrement('remaining_order_quantity', $item['sales_order_quantity']);

            $salesOrder->items()->create($item);

            $orderId = $item['order_id'];
            if (! isset($processedOrders[$orderId])) {
                $processedOrders[$orderId] = true;
                $this->documentReferenceService->createReference(
                    $documentUuid,
                    'sales_order',
                    $salesOrder->id,
                    'order',
                    $orderId,
                );
            }
        }
    }

    public function autoCompleteIfAllDelivered(SalesOrder $salesOrder): void
    {
        $salesOrder->load('items');

        $allDelivered = $salesOrder->items->every(
            fn ($item) => (float) $item->remaining_sales_quantity <= 0
        );

        if (! $allDelivered || $salesOrder->status === 'completed') {
            return;
        }

        $statusChain = ['draft', 'confirmed', 'approved', 'processing', 'ready_for_delivery', 'completed'];
        $currentStatus = $salesOrder->status;

        $startIndex = array_search($currentStatus, $statusChain);
        if ($startIndex === false || $startIndex >= count($statusChain) - 1) {
            return;
        }

        for ($i = $startIndex + 1; $i < count($statusChain); $i++) {
            $nextStatus = $statusChain[$i];
            if ($this->statusTransitionService->canTransition($currentStatus, $nextStatus)) {
                $salesOrder = $this->statusTransitionService->transition($salesOrder, $nextStatus);
                $currentStatus = $nextStatus;
            }
        }
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('sales_order_serial', 'like', "%{$search}%")
              ->orWhere('status', 'like', "%{$search}%")
              ->orWhereHas('customer', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
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

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['sales_order_serial'])) {
            $query->where('sales_order_serial', 'like', "%{$filters['sales_order_serial']}%");
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('sales_order_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('sales_order_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['sales_order_serial', 'sales_order_date', 'created_at', 'grand_total', 'status'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}

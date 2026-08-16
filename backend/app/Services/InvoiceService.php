<?php

namespace App\Services;

use App\Models\DeliveryChallanItem;
use App\Models\Invoice;
use App\Models\PaymentItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceSerialGeneratorService $serialGenerator,
        private readonly InvoiceStatusTransitionService $statusTransitionService,
        private readonly ActivityLogService $activityLogService,
        private readonly SourceDocumentOwnershipService $sourceDocumentOwnership,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Invoice::query()->with(['customer', 'creator', 'items']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 10), 100));
    }

    public function store(array $data): Invoice
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
            $data['round_off'] = $calculated['round_off'];
            $data['grand_total'] = $calculated['grand_total'];

            /** @var Invoice $invoice */
            $invoice = Invoice::create($data);

            $this->syncItems($invoice, $preparedItems);

            $this->activityLogService->logCreate('invoice', 'invoice', $invoice->id, $data);

            return $invoice->load([
                'customer',
                'creator',
                'items.deliveryChallan',
                'items.deliveryChallanItem',
                'items.orderBooking',
            ]);
        });
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $originalStatus = $invoice->status;

            $items = $data['items'] ?? null;
            unset($data['items']);

            $newStatus = $data['status'] ?? null;
            unset($data['status']);

            if ($newStatus !== null && $newStatus !== $originalStatus) {
                $this->statusTransitionService->validateTransition($originalStatus, $newStatus);
                $invoice->update(['status' => $newStatus, 'updated_by' => auth()->id()]);
                $this->activityLogService->logAction('invoice', 'invoice', $invoice->id, "status_changed:{$originalStatus}->{$newStatus}");
            }

            if (! empty($data)) {
                $data['updated_by'] = auth()->id();
                $oldData = $invoice->only(array_keys($data));
                $invoice->update($data);
                $this->activityLogService->logUpdate('invoice', 'invoice', $invoice->id, $oldData, $data);
            }

            if ($items !== null) {
                $this->restoreInvoiceItemQuantities($invoice);

                $preparedItems = $this->prepareItems($items, $data['customer_id'] ?? $invoice->customer_id);
                $calculated = $this->calculateTotals($preparedItems);
                $invoice->update([
                    'subtotal' => $calculated['subtotal'],
                    'discount_amount' => $calculated['discount_amount'],
                    'tax_amount' => $calculated['tax_amount'],
                    'round_off' => $calculated['round_off'],
                    'grand_total' => $calculated['grand_total'],
                    'updated_by' => auth()->id(),
                ]);

                $invoice->items()->delete();
                $this->syncItems($invoice, $preparedItems);

                $paidAmount = (float) PaymentItem::where('invoice_id', $invoice->id)
                    ->whereHas('payment', function ($q) {
                        $q->where('payment_status', 'confirmed');
                    })
                    ->sum('paid_amount');

                $grandTotal = (float) $invoice->fresh()->grand_total;

                if ($paidAmount >= $grandTotal && $grandTotal > 0) {
                    $paymentStatus = 'paid';
                } elseif ($paidAmount > 0) {
                    $paymentStatus = 'partial';
                } else {
                    $paymentStatus = 'unpaid';
                }

                $invoice->update([
                    'paid_amount' => $paidAmount,
                    'payment_status' => $paymentStatus,
                    'updated_by' => auth()->id(),
                ]);
            }

            return $invoice->load([
                'customer',
                'creator',
                'items.deliveryChallan',
                'items.deliveryChallanItem',
                'items.orderBooking',
            ]);
        });
    }

    public function findTrashed(int $id): Invoice
    {
        return Invoice::withoutGlobalScopes()->withTrashed()->findOrFail($id);
    }

    public function delete(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $this->restoreInvoiceItemQuantities($invoice);

            $this->activityLogService->logDelete('invoice', 'invoice', $invoice->id);

            $invoice->delete();
        });
    }

    public function restore(Invoice $invoice): Invoice
    {
        $invoice->restore();

        $this->activityLogService->logRestore('invoice', 'invoice', $invoice->id);

        return $invoice->load([
            'customer',
            'creator',
            'items',
        ]);
    }

    public function getInvoiceableItems(int $deliveryChallanId): array
    {
        $this->sourceDocumentOwnership->deliveryChallan($deliveryChallanId);

        $items = DeliveryChallanItem::with([
            'deliveryChallan.customer',
            'orderBooking',
        ])
            ->where('delivery_challan_id', $deliveryChallanId)
            ->where('remaining_invoice_quantity', '>', 0)
            ->get()
            ->map(function (DeliveryChallanItem $item) {
                $remaining = (float) $item->remaining_invoice_quantity;

                if ($remaining <= 0) {
                    return null;
                }

                return [
                    'delivery_challan_item_id' => $item->id,
                    'delivery_challan_id' => $item->delivery_challan_id,
                    'delivery_challan_serial' => $item->deliveryChallan?->serial,
                    'order_booking_id' => $item->order_booking_id,
                    'order_booking_item_id' => $item->order_booking_item_id,
                    'quotation_id' => $item->quotation_id,
                    'quotation_item_id' => $item->quotation_item_id,
                    'item_description' => $item->item_description,
                    'unit' => $item->unit,
                    'delivered_quantity' => (float) $item->delivered_quantity,
                    'already_invoiced' => (float) $item->invoiced_quantity,
                    'available_for_invoicing' => $remaining,
                    'unit_price' => (float) $item->unit_price,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        return $items;
    }

    private function prepareItems(array $items, int $customerId): array
    {
        $prepared = [];

        foreach ($items as $item) {
            $dcItem = $this->sourceDocumentOwnership->deliveryChallanItem($item['delivery_challan_item_id']);
            $this->sourceDocumentOwnership->ensureMatchesCustomer($customerId, $dcItem->deliveryChallan);
            $dcItem->loadMissing([
                'deliveryChallan',
                'orderBooking',
            ]);

            $lockedDcItem = DeliveryChallanItem::lockForUpdate()->findOrFail($dcItem->id);
            $invoicedQuantity = (float) $item['invoiced_quantity'];
            $availableQuantity = (float) $lockedDcItem->remaining_invoice_quantity;

            if ($availableQuantity <= 0) {
                $availableQuantity = (float) $lockedDcItem->delivered_quantity - (float) $lockedDcItem->invoiced_quantity;
            }

            if ($invoicedQuantity > $availableQuantity) {
                throw new \InvalidArgumentException(
                    "Invoice quantity ({$invoicedQuantity}) exceeds available quantity ({$availableQuantity}) for delivery challan item #{$dcItem->id}."
                );
            }

            $unitPrice = (float) ($item['unit_price'] ?? $dcItem->unit_price);
            $lineTotal = $invoicedQuantity * $unitPrice;

            $prepared[] = [
                'delivery_challan_id' => $dcItem->delivery_challan_id,
                'delivery_challan_item_id' => $dcItem->id,
                'order_booking_id' => $dcItem->order_booking_id,
                'order_booking_item_id' => $dcItem->order_booking_item_id,
                'quotation_id' => $dcItem->quotation_id,
                'quotation_item_id' => $dcItem->quotation_item_id,
                'item_description' => $item['item_description'] ?? $dcItem->item_description,
                'unit' => $item['unit'] ?? $dcItem->unit,
                'delivered_quantity' => (float) $dcItem->delivered_quantity,
                'invoiced_quantity' => $invoicedQuantity,
                'remaining_invoice_quantity' => $availableQuantity - $invoicedQuantity,
                'unit_price' => $unitPrice,
                'line_total' => round($lineTotal, 2),
                'remarks' => $item['remarks'] ?? null,
            ];
        }

        return $prepared;
    }

    private function calculateTotals(array &$preparedItems): array
    {
        $subtotal = 0;

        foreach ($preparedItems as &$item) {
            $quantity = (float) $item['invoiced_quantity'];
            $unitPrice = (float) $item['unit_price'];
            $lineTotal = $quantity * $unitPrice;
            $item['line_total'] = round($lineTotal, 2);
            $subtotal += $lineTotal;
        }
        unset($item);

        $grandTotal = round($subtotal, 2);
        $roundOff = round($grandTotal - $subtotal, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => 0,
            'tax_amount' => 0,
            'round_off' => $roundOff,
            'grand_total' => $grandTotal,
        ];
    }

    private function syncItems(Invoice $invoice, array $preparedItems): void
    {
        foreach ($preparedItems as $item) {
            $invoice->items()->create($item);

            $lockedDcItem = DeliveryChallanItem::lockForUpdate()->findOrFail($item['delivery_challan_item_id']);
            $lockedDcItem->increment('invoiced_quantity', $item['invoiced_quantity']);
            $lockedDcItem->decrement('remaining_invoice_quantity', $item['invoiced_quantity']);
        }
    }

    private function restoreInvoiceItemQuantities(Invoice $invoice): void
    {
        foreach ($invoice->items as $invItem) {
            $dcItem = $this->sourceDocumentOwnership->deliveryChallanItem($invItem->delivery_challan_item_id);
            if ($dcItem) {
                $lockedDcItem = DeliveryChallanItem::lockForUpdate()->findOrFail($dcItem->id);
                $lockedDcItem->decrement('invoiced_quantity', (float) $invItem->invoiced_quantity);
                $lockedDcItem->increment('remaining_invoice_quantity', (float) $invItem->invoiced_quantity);
            }
        }
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('serial', 'like', "%{$search}%")
              ->orWhereHas('customer', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
              })
              ->orWhereHas('items', function (Builder $q) use ($search) {
                  $q->whereHas('deliveryChallan', function (Builder $q) use ($search) {
                      $q->where('serial', 'like', "%{$search}%");
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
            $query->whereDate('invoice_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['serial', 'invoice_date', 'due_date', 'created_at', 'grand_total', 'status'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}

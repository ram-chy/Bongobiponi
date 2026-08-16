<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;

class PurchaseService
{
    public function __construct(
        private readonly PurchaseSerialGeneratorService $serialGenerator,
        private readonly InventoryService $inventoryService,
        private readonly PurchaseOrderSynchronizationService $orderSyncService,
    ) {}

    public function list(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Purchase::query()->with(['supplier', 'publisher', 'creator', 'items']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function store(array $data): Purchase
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $data['purchase_no'] = $this->serialGenerator->generate();

            /** @var Purchase $purchase */
            $purchase = Purchase::create($data);

            foreach ($items as $item) {
                $item['total'] = $item['received_quantity'] * $item['purchase_price'];
                $purchase->items()->create($item);
            }

            return $purchase->load(['supplier', 'publisher', 'creator', 'items.book']);
        });
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        if ($purchase->status !== 'draft') {
            throw new \RuntimeException('Only draft purchases can be updated.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($purchase, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);

            $purchase->update($data);

            if ($items !== null) {
                $purchase->items()->delete();
                foreach ($items as $item) {
                    $item['total'] = $item['received_quantity'] * $item['purchase_price'];
                    $purchase->items()->create($item);
                }
            }

            return $purchase->load(['supplier', 'publisher', 'creator', 'items.book']);
        });
    }

    public function confirm(Purchase $purchase): Purchase
    {
        if ($purchase->status !== 'draft') {
            throw new \RuntimeException('Only draft purchases can be confirmed.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($purchase) {
            $purchase->update(['status' => 'confirmed', 'updated_by' => auth()->id()]);

            $this->increaseStockForPurchase($purchase);

            $this->orderSyncService->syncAfterPurchase($purchase);

            return $purchase->load(['supplier', 'publisher', 'creator', 'items.book']);
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        $cancellableStatuses = ['draft', 'confirmed'];
        if (! in_array($purchase->status, $cancellableStatuses)) {
            throw new \RuntimeException('Only draft or confirmed purchases can be cancelled.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($purchase) {
            if ($purchase->status === 'confirmed') {
                $this->reverseStockForPurchase($purchase);
            }

            $purchase->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);

            return $purchase->load(['supplier', 'publisher', 'creator', 'items.book']);
        });
    }

    public function findTrashed(int $id): Purchase
    {
        return Purchase::withoutGlobalScopes()->withTrashed()->findOrFail($id);
    }

    public function delete(Purchase $purchase): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($purchase) {
            if ($purchase->status === 'confirmed') {
                $this->reverseStockForPurchase($purchase);
            }

            $purchase->delete();
        });
    }

    public function restore(Purchase $purchase): Purchase
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($purchase) {
            $purchase->restore();

            if ($purchase->status === 'confirmed') {
                $this->increaseStockForPurchase($purchase);
            }

            return $purchase->load(['supplier', 'publisher', 'creator', 'items.book']);
        });
    }

    public function reverseStockForPurchase(Purchase $purchase): void
    {
        $transactions = InventoryTransaction::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->get();

        foreach ($transactions as $transaction) {
            $this->inventoryService->reverseTransaction($transaction);
        }
    }

    public function increaseStockForPurchase(Purchase $purchase): void
    {
        $purchase->load('items');

        foreach ($purchase->items as $item) {
            $this->inventoryService->increaseStock(
                $item->book_id,
                $item->received_quantity,
                InventoryTransactionType::PURCHASE,
                Purchase::class,
                $purchase->id,
                $purchase->purchase_date->format('Y-m-d'),
                "Purchase {$purchase->purchase_no}",
                $purchase->created_by,
            );
        }
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        $search = str_replace(['%', '_'], ['\\%', '\\_'], $search);

        return $query->where(function (Builder $q) use ($search) {
            $q->where('purchase_no', 'like', "%{$search}%")
              ->orWhere('invoice_no', 'like', "%{$search}%")
              ->orWhereHas('supplier', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
              })
              ->orWhereHas('publisher', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['publisher_id'])) {
            $query->where('publisher_id', $filters['publisher_id']);
        }

        if (! empty($filters['purchase_type'])) {
            $query->where('purchase_type', $filters['purchase_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('purchase_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('purchase_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['purchase_no', 'purchase_date', 'created_at', 'status'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}

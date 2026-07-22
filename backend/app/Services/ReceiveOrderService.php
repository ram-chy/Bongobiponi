<?php

namespace App\Services;

use App\Models\ReceiveOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ReceiveOrderService
{
    public function __construct(
        private readonly ReceiveOrderSerialGeneratorService $serialGenerator,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = ReceiveOrder::query()->with(['supplier', 'customer', 'creator', 'items']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function store(array $data): ReceiveOrder
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $data['order_no'] = $this->serialGenerator->generate();

            /** @var ReceiveOrder $receiveOrder */
            $receiveOrder = ReceiveOrder::create($data);

            foreach ($items as $item) {
                $receiveOrder->items()->create($item);
            }

            return $receiveOrder->load(['supplier', 'creator', 'items.book']);
        });
    }

    public function update(ReceiveOrder $receiveOrder, array $data): ReceiveOrder
    {
        if ($receiveOrder->status !== 'draft') {
            throw new \RuntimeException('Only draft receive orders can be updated.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($receiveOrder, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);

            $receiveOrder->update($data);

            if ($items !== null) {
                $receiveOrder->items()->delete();
                foreach ($items as $item) {
                    $receiveOrder->items()->create($item);
                }
            }

            return $receiveOrder->load(['supplier', 'creator', 'items.book']);
        });
    }

    public function findTrashed(int $id): ReceiveOrder
    {
        return ReceiveOrder::withoutGlobalScopes()->withTrashed()->findOrFail($id);
    }

    public function restore(ReceiveOrder $receiveOrder): ReceiveOrder
    {
        $receiveOrder->restore();

        return $receiveOrder->load(['supplier', 'creator', 'items.book']);
    }

    public function approve(ReceiveOrder $receiveOrder): ReceiveOrder
    {
        if ($receiveOrder->status !== 'draft') {
            throw new \RuntimeException('Only draft receive orders can be approved.');
        }

        $receiveOrder->update(['status' => 'approved']);

        return $receiveOrder->load(['supplier', 'creator', 'items.book']);
    }

    public function receive(ReceiveOrder $receiveOrder, array $receivedItems): ReceiveOrder
    {
        if ($receiveOrder->status !== 'approved' && $receiveOrder->status !== 'partially_received') {
            throw new \RuntimeException('Only approved or partially received orders can receive items.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($receiveOrder, $receivedItems) {
            foreach ($receivedItems as $receivedItem) {
                $item = $receiveOrder->items()->findOrFail($receivedItem['id']);

                $newReceivedQty = $item->received_quantity + (int) $receivedItem['received_quantity'];

                if ($newReceivedQty > $item->ordered_quantity) {
                    throw new \RuntimeException(
                        "Received quantity ({$newReceivedQty}) cannot exceed ordered quantity ({$item->ordered_quantity}) for item {$item->id}."
                    );
                }

                $item->update(['received_quantity' => $newReceivedQty]);
            }

            $receiveOrder->refresh()->load('items');

            $allFullyReceived = true;
            foreach ($receiveOrder->items as $item) {
                if ($item->received_quantity < $item->ordered_quantity) {
                    $allFullyReceived = false;
                    break;
                }
            }

            $newStatus = $allFullyReceived ? 'completed' : 'partially_received';
            $receiveOrder->update(['status' => $newStatus]);

            return $receiveOrder->load(['supplier', 'creator', 'items.book']);
        });
    }

    public function cancel(ReceiveOrder $receiveOrder): ReceiveOrder
    {
        $cancellableStatuses = ['draft', 'approved'];
        if (! in_array($receiveOrder->status, $cancellableStatuses)) {
            throw new \RuntimeException('Only draft or approved receive orders can be cancelled.');
        }

        $receiveOrder->update(['status' => 'cancelled']);

        return $receiveOrder->load(['supplier', 'creator', 'items.book']);
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('order_no', 'like', "%{$search}%")
              ->orWhere('reference_no', 'like', "%{$search}%")
              ->orWhere('status', 'like', "%{$search}%")
              ->orWhereHas('supplier', function (Builder $q) use ($search) {
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

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('expected_delivery_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('expected_delivery_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['order_no', 'expected_delivery_date', 'created_at', 'status'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}

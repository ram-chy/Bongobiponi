<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderStockReservation;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class OrderStockReservationService
{
    /**
     * Reserve stock for an entire order atomically.
     * All items must be available or the entire reservation fails.
     *
     * @throws \RuntimeException If stock cannot be fully allocated
     */
    public function reserveForOrder(Order $order): void
    {
        $order->loadMissing(['items.book']);

        DB::transaction(function () use ($order) {
            // Lock all relevant stock rows in deterministic order (by book_id)
            $bookIds = $order->items
                ->pluck('book_id')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            if (empty($bookIds)) {
                return; // No items to reserve
            }

            // Lock stock rows to prevent concurrent modifications
            $stocks = Stock::whereIn('book_id', $bookIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('book_id');

            // Verify all items can be fully allocated
            foreach ($order->items as $item) {
                if ($item->book_id === null || $item->book === null) {
                    throw new \RuntimeException("Order item #{$item->id} has no valid book reference.");
                }

                $required = (int) $item->remaining_order_quantity;
                if ($required <= 0) {
                    continue; // Skip items with no remaining quantity
                }

                $stock = $stocks->get($item->book_id);
                $physicalStock = $stock?->current_quantity ?? 0;
                $activeReservations = $this->getActiveReservationQuantity($item->book_id, excludeOrderId: $order->id);
                $available = $physicalStock - $activeReservations;

                if ($available < $required) {
                    throw new \RuntimeException(
                        "Insufficient stock for '{$item->book->title}'. Required: {$required}, Available: {$available}."
                    );
                }
            }

            // All checks passed, create reservations
            foreach ($order->items as $item) {
                if ($item->book_id === null || $item->book === null) {
                    continue;
                }

                $quantity = (int) $item->remaining_order_quantity;
                if ($quantity <= 0) {
                    continue;
                }

                // Check if reservation already exists (idempotency)
                $existing = OrderStockReservation::where('order_id', $order->id)
                    ->where('book_id', $item->book_id)
                    ->where('status', 'active')
                    ->first();

                if ($existing) {
                    // Already reserved, skip
                    continue;
                }

                OrderStockReservation::create([
                    'order_id' => $order->id,
                    'book_id' => $item->book_id,
                    'quantity' => $quantity,
                    'status' => 'active',
                ]);
            }
        });
    }

    /**
     * Release all active reservations for an order.
     */
    public function releaseForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            OrderStockReservation::where('order_id', $order->id)
                ->where('status', 'active')
                ->update(['status' => 'released']);
        });
    }

    /**
     * Mark reservations as consumed when physical stock is deducted.
     *
     * Reservations of the delivering order are consumed first; if the delivered
     * quantity exceeds them, the remainder is consumed FIFO across the other
     * active reservations for the book.
     *
     * @param int $bookId The book whose stock was consumed
     * @param int $quantity The quantity consumed
     * @param int $inventoryTransactionId The inventory transaction that caused the consumption
     * @param int|null $preferOrderId The order being fulfilled (consume its reservations first)
     */
    public function consumeForBook(
        int $bookId,
        int $quantity,
        int $inventoryTransactionId,
        ?int $preferOrderId = null,
    ): void {
        DB::transaction(function () use ($bookId, $quantity, $inventoryTransactionId, $preferOrderId) {
            // Find active reservations for this book, ordered by creation (FIFO)
            $reservations = OrderStockReservation::where('book_id', $bookId)
                ->where('status', 'active')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $remainingToConsume = $quantity;

            if ($preferOrderId !== null) {
                foreach ($reservations->where('order_id', $preferOrderId) as $reservation) {
                    if ($remainingToConsume <= 0) {
                        break;
                    }

                    $remainingToConsume = $this->consumeFromReservation($reservation, $remainingToConsume, $inventoryTransactionId);
                }
            }

            foreach ($reservations->where('status', 'active') as $reservation) {
                if ($remainingToConsume <= 0) {
                    break;
                }

                $remainingToConsume = $this->consumeFromReservation($reservation, $remainingToConsume, $inventoryTransactionId);
            }
        });
    }

    private function consumeFromReservation(
        OrderStockReservation $reservation,
        int $remainingToConsume,
        int $inventoryTransactionId,
    ): int {
        if ($reservation->quantity <= $remainingToConsume) {
            // Fully consume this reservation
            $reservation->update([
                'status' => 'consumed',
                'consumed_by_inventory_transaction_id' => $inventoryTransactionId,
            ]);

            return $remainingToConsume - $reservation->quantity;
        }

        // Partially consume this reservation
        $reservation->update([
            'quantity' => $reservation->quantity - $remainingToConsume,
            'consumed_by_inventory_transaction_id' => $inventoryTransactionId,
        ]);

        return 0;
    }

    /**
     * Restore reservations that were consumed by a reversed inventory transaction.
     *
     * When a SALE transaction (e.g. from a deleted delivery challan) is reversed,
     * the physical stock comes back and the reservations that transaction consumed
     * must return to an active state so availability remains accurate and the
     * order can be re-fulfilled.
     */
    public function restoreConsumedForTransaction(int $inventoryTransactionId, int $quantity): void
    {
        DB::transaction(function () use ($inventoryTransactionId, $quantity) {
            $linked = OrderStockReservation::where('consumed_by_inventory_transaction_id', $inventoryTransactionId)
                ->lockForUpdate()
                ->get();

            if ($linked->isEmpty()) {
                return;
            }

            $remaining = $quantity;

            // Fully-consumed reservations keep their original quantity untouched,
            // so restoring them only requires flipping the status back to active.
            foreach ($linked->where('status', 'consumed') as $reservation) {
                if ($remaining <= 0) {
                    break;
                }

                $reservation->update([
                    'status' => 'active',
                    'consumed_by_inventory_transaction_id' => null,
                ]);

                $remaining -= $reservation->quantity;
            }

            // At most one reservation can be partially consumed (the last in FIFO
            // order). Give it back the remaining reversed quantity.
            if ($remaining > 0) {
                $partial = $linked->where('status', 'active')->first();

                if ($partial) {
                    $partial->increment('quantity', $remaining);
                    $partial->update(['consumed_by_inventory_transaction_id' => null]);
                }
            }
        });
    }

    /**
     * Get the total active reservation quantity for a book.
     */
    public function getActiveReservationQuantity(int $bookId, ?int $excludeOrderId = null): int
    {
        $query = OrderStockReservation::where('book_id', $bookId)
            ->where('status', 'active');

        if ($excludeOrderId !== null) {
            $query->where('order_id', '!=', $excludeOrderId);
        }

        return (int) $query->sum('quantity');
    }

    /**
     * Get available quantity for a book (physical stock - active reservations).
     */
    public function getAvailableQuantity(int $bookId, ?int $excludeOrderId = null): int
    {
        $stock = Stock::where('book_id', $bookId)->first();
        $physicalStock = $stock?->current_quantity ?? 0;
        $activeReservations = $this->getActiveReservationQuantity($bookId, $excludeOrderId);

        return max(0, $physicalStock - $activeReservations);
    }

    /**
     * Get reservation details for an order.
     *
     * Released reservations are preserved (never deleted) so the Order's
     * allocation history remains auditable after cancellation.
     *
     * @return array<int, array{
     *     id: int,
     *     product_id: int,
     *     required_quantity: int,
     *     reserved_quantity: int,
     *     status: string
     * }>
     */
    public function getReservationsForOrder(Order $order): array
    {
        $reservations = OrderStockReservation::where('order_id', $order->id)
            ->whereIn('status', ['active', 'consumed', 'released'])
            ->get()
            ->keyBy('book_id');

        $result = [];

        foreach ($order->items as $item) {
            if ($item->book_id === null) {
                continue;
            }

            $required = (int) $item->remaining_order_quantity;
            $reservation = $reservations->get($item->book_id);

            $reservedQuantity = $reservation && $reservation->status === 'active' ? $reservation->quantity : 0;
            $status = $reservation
                ? match ($reservation->status) {
                    'active' => 'allocated',
                    'consumed' => 'consumed',
                    'released' => 'released',
                    default => 'waiting',
                }
                : 'waiting';

            $result[] = [
                'id' => $reservation?->id ?? 0,
                'product_id' => $item->book_id,
                'required_quantity' => $required,
                'reserved_quantity' => $reservedQuantity,
                'status' => $status,
            ];
        }

        return $result;
    }

    /**
     * Check if an order can be fully allocated.
     */
    public function canFullyAllocate(Order $order): bool
    {
        $order->loadMissing(['items.book']);

        foreach ($order->items as $item) {
            if ($item->book_id === null || $item->book === null) {
                return false;
            }

            $required = (int) $item->remaining_order_quantity;
            if ($required <= 0) {
                continue;
            }

            $available = $this->getAvailableQuantity($item->book_id, $order->id);

            if ($available < $required) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find TO_PROCURE orders that can be fully allocated, sorted by FIFO.
     */
    public function findEligibleOrdersForAllocation(int $bookId): \Illuminate\Database\Eloquent\Collection
    {
        return Order::query()
            ->where('status', 'to_procure')
            ->whereHas('items', function ($query) use ($bookId) {
                $query->where('book_id', $bookId)
                    ->where('remaining_order_quantity', '>', 0);
            })
            ->with(['items.book'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }
}
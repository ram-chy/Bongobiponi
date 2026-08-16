<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

class PurchaseOrderSynchronizationService
{
    public function __construct(
        private readonly OrderAvailabilityService $availabilityService,
        private readonly OrderStatusTransitionService $statusTransitionService,
        private readonly \App\Services\OrderStockReservationService $reservationService,
    ) {}

    /**
     * After a purchase is confirmed, re-evaluate every TO_PROCURE order that
     * needs a book included in the purchase. Orders that become fully available
     * are moved to TO_PACK via OrderStatusTransitionService (never bypassed).
     *
     * @return array<string, mixed>
     */
    public function syncAfterPurchase(Purchase $purchase): array
    {
        $purchase->loadMissing('items');

        $bookIds = $purchase->items
            ->pluck('book_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($bookIds)) {
            return $this->buildResult($purchase, [], []);
        }

        $orders = $this->findAffectedEligibleOrders($bookIds);

        return $this->reEvaluateOrders($purchase, $orders);
    }

    /**
     * After an Order cancellation releases stock, re-evaluate every TO_PROCURE
     * order that needs one of the released books. Orders that become fully
     * available are moved to TO_PACK using the exact same FIFO / full-allocation
     * logic as the purchase path. Runs in its own transaction AFTER the
     * cancellation has committed, so a re-allocation failure can never roll back
     * a valid cancellation.
     *
     * @param array<int, int> $bookIds
     * @return array<string, mixed>
     */
    public function syncAfterRelease(array $bookIds): array
    {
        $bookIds = array_values(array_unique(array_filter($bookIds)));

        if (empty($bookIds)) {
            return $this->buildResult(null, [], []);
        }

        $orders = $this->findAffectedEligibleOrders($bookIds);

        return $this->reEvaluateOrders(null, $orders);
    }

    private function findAffectedEligibleOrders(array $bookIds): Collection
    {
        return Order::query()
            ->whereIn('id', function (Builder $query) use ($bookIds) {
                $query->select('order_id')
                    ->distinct()
                    ->from('order_items')
                    ->whereIn('book_id', $bookIds);
            })
            ->where('status', OrderStatus::ToProcure->value)
            ->with(['items.book'])
            ->get();
    }

    private function reEvaluateOrders(?Purchase $purchase, Collection $orders): array
    {
        $movedToPack = [];
        $remainingToProcure = [];

        // Sort orders by FIFO (oldest first) for deterministic allocation
        $sortedOrders = $orders->sortBy([
            ['created_at', 'asc'],
            ['id', 'asc'],
        ])->values();

        foreach ($sortedOrders as $order) {
            $availability = $this->availabilityService->check($order);

            if ($availability['status'] === 'unverifiable') {
                $remainingToProcure[] = [
                    'order_id' => $order->id,
                    'reason' => 'availability cannot be verified (missing/orphaned book reference)',
                ];
                continue;
            }

            if (! $availability['fully_available']) {
                $remainingToProcure[] = [
                    'order_id' => $order->id,
                    'reason' => 'order not fully available after purchase',
                ];
                continue;
            }

            try {
                // This will attempt reservation and transition to TO_PACK
                $this->statusTransitionService->transitionBySystem(
                    $order,
                    OrderStatus::ToPack,
                    'Order became fully available'
                );
                $movedToPack[] = $order->id;
            } catch (InvalidArgumentException $e) {
                $remainingToProcure[] = [
                    'order_id' => $order->id,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $this->buildResult($purchase, $movedToPack, $remainingToProcure);
    }

    /**
     * @param Purchase|null $purchase null for cancellation-driven re-allocation
     * @param array<int, int> $movedToPack
     * @param array<int, array{order_id: int, reason: string}> $remainingToProcure
     * @return array<string, mixed>
     */
    private function buildResult(?Purchase $purchase, array $movedToPack, array $remainingToProcure): array
    {
        return [
            'purchase_id' => $purchase?->id,
            'affected_orders' => count($movedToPack) + count($remainingToProcure),
            'orders_moved_to_pack' => count($movedToPack),
            'orders_remaining_to_procure' => count($remainingToProcure),
            'moved_order_ids' => array_values($movedToPack),
            'remaining_order_ids' => array_values(array_column($remainingToProcure, 'order_id')),
        ];
    }
}

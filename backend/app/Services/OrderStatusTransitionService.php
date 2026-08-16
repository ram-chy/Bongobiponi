<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderStatusTransitionService
{
    private const TRANSITIONS = [
        'intake' => ['to_procure', 'to_pack', 'cancelled'],
        'to_procure' => ['to_pack', 'cancelled'],
        'to_pack' => ['packed', 'cancelled'],
        'packed' => ['dispatched'],
        'dispatched' => ['delivered', 'rto'],
        'delivered' => [],
        'rto' => [],
        'cancelled' => [],
    ];

    private const DEFAULT_REASONS = [
        'intake' => 'Order created',
        'to_procure' => 'Order requires procurement',
        'to_pack' => 'Order ready for packing',
        'packed' => 'Order packed',
        'dispatched' => 'Order dispatched',
        'delivered' => 'Order delivered',
        'rto' => 'Order returned to origin',
        'cancelled' => 'Order cancelled',
    ];

    private const RESERVATION_REQUIRED_TRANSITIONS = ['to_pack'];

    public function allowedTransitions(): array
    {
        return self::TRANSITIONS;
    }

    public function canTransition(string $from, string $to): bool
    {
        if (! isset(self::TRANSITIONS[$from])) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from]);
    }

    public function transition(Order $order, OrderStatus $newStatus, ?string $reason = null, ?int $changedBy = null): Order
    {
        $currentStatus = $order->status;

        if (! $this->canTransition($currentStatus->value, $newStatus->value)) {
            throw new InvalidArgumentException(
                "Cannot transition order from '{$currentStatus->value}' to '{$newStatus->value}'."
            );
        }

        if (in_array($newStatus->value, self::RESERVATION_REQUIRED_TRANSITIONS, true)) {
            $this->attemptReservation($order);
        }

        return $this->performTransition($order, $newStatus, $changedBy ?? auth()->id(), $reason);
    }

    /**
     * System-generated transitions (e.g. Purchase-driven TO_PROCURE -> TO_PACK).
     * The actor is intentionally null; it is displayed as "System".
     */
    public function transitionBySystem(Order $order, OrderStatus $newStatus, ?string $reason = null): Order
    {
        if (in_array($newStatus->value, self::RESERVATION_REQUIRED_TRANSITIONS, true)) {
            $this->attemptReservation($order);
        }

        return $this->performTransition($order, $newStatus, null, $reason);
    }

    /**
     * Legacy fulfillment recalculation path (delivery challan flow).
     *
     * The delivery challan workflow has historically moved an order directly to
     * DELIVERED once all quantities are delivered, and back to INTAKE when a
     * challan is removed. These jumps are not part of the legal transition
     * matrix, so this method intentionally bypasses matrix validation while
     * still writing an audit record. It is the smallest safe change that keeps
     * every successful order status change recorded.
     */
    public function transitionForFulfillment(Order $order, OrderStatus $newStatus, ?string $reason = null): Order
    {
        return $this->performTransition($order, $newStatus, auth()->id(), $reason);
    }

    public function validateTransition(string $from, string $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                "Cannot transition order from '{$from}' to '{$to}'."
            );
        }
    }

    private function attemptReservation(Order $order): void
    {
        $reservationService = app(\App\Services\OrderStockReservationService::class);

        if (! $reservationService->canFullyAllocate($order)) {
            throw new InvalidArgumentException(
                'Cannot transition to to_pack. Insufficient stock available for one or more order items.'
            );
        }

        $reservationService->reserveForOrder($order);
    }

    /**
     * Release every active stock reservation when an Order is cancelled.
     * Only ACTIVE reservations are touched; consumed (already physically
     * deducted) reservations are never released by ordinary cancellation.
     * Physical inventory is never modified here.
     */
    private function releaseReservationOnCancellation(Order $order): void
    {
        app(\App\Services\OrderStockReservationService::class)->releaseForOrder($order);
    }

    private function performTransition(Order $order, OrderStatus $newStatus, ?int $changedBy, ?string $reason): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $changedBy, $reason) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            $fromStatus = $lockedOrder->status;

            if ($fromStatus === $newStatus) {
                return $lockedOrder->fresh();
            }

            if ($newStatus === OrderStatus::Cancelled) {
                $this->releaseReservationOnCancellation($lockedOrder);
            }

            $lockedOrder->update(['status' => $newStatus->value]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $fromStatus?->value,
                'to_status' => $newStatus->value,
                'changed_by' => $changedBy,
                'reason' => $reason ?? self::DEFAULT_REASONS[$newStatus->value],
            ]);

            return $lockedOrder->fresh();
        });
    }
}

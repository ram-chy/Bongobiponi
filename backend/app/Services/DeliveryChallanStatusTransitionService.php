<?php

namespace App\Services;

use App\Models\DeliveryChallan;
use InvalidArgumentException;

class DeliveryChallanStatusTransitionService
{
    private const TRANSITIONS = [
        'draft' => ['ready', 'cancelled'],
        'ready' => ['dispatched', 'cancelled'],
        'dispatched' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        if (! isset(self::TRANSITIONS[$from])) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from]);
    }

    public function transition(DeliveryChallan $deliveryChallan, string $newStatus): DeliveryChallan
    {
        $currentStatus = $deliveryChallan->status;

        if (! $this->canTransition($currentStatus, $newStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition from '{$currentStatus}' to '{$newStatus}'."
            );
        }

        $deliveryChallan->update(['status' => $newStatus]);

        return $deliveryChallan->fresh();
    }

    public function validateTransition(string $from, string $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                "Cannot transition from '{$from}' to '{$to}'."
            );
        }
    }
}

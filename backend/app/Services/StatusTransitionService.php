<?php

namespace App\Services;

use App\Models\SalesOrder;
use InvalidArgumentException;

class StatusTransitionService
{
    private const TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['approved', 'cancelled'],
        'approved' => ['processing', 'cancelled'],
        'processing' => ['ready_for_delivery'],
        'ready_for_delivery' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

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

    public function transition(SalesOrder $salesOrder, string $newStatus): SalesOrder
    {
        $currentStatus = $salesOrder->status;

        if (! $this->canTransition($currentStatus, $newStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition from '{$currentStatus}' to '{$newStatus}'."
            );
        }

        $data = ['status' => $newStatus];

        if ($newStatus === 'confirmed') {
            $data['confirmed_at'] = now();
        }

        if ($newStatus === 'approved') {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        $salesOrder->update($data);

        return $salesOrder->fresh();
    }
}

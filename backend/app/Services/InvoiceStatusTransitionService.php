<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use InvalidArgumentException;

class InvoiceStatusTransitionService
{
    private const TRANSITIONS = [
        'draft' => ['issued', 'cancelled'],
        'issued' => ['partially_paid', 'paid', 'cancelled'],
        'partially_paid' => ['paid', 'cancelled'],
        'paid' => [],
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

    public function transition(Invoice $invoice, InvoiceStatus $newStatus): Invoice
    {
        $currentStatus = $invoice->status;

        if (! $this->canTransition($currentStatus, $newStatus->value)) {
            throw new InvalidArgumentException(
                "Cannot transition from '{$currentStatus}' to '{$newStatus->value}'."
            );
        }

        $invoice->update(['status' => $newStatus->value]);

        return $invoice->fresh();
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

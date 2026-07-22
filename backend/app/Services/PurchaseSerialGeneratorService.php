<?php

namespace App\Services;

use App\Models\Purchase;

class PurchaseSerialGeneratorService
{
    public function generate(): string
    {
        $year = now()->format('Y');
        $pattern = "PO-{$year}-%";

        $lastSerial = Purchase::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->where('purchase_no', 'like', $pattern)
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->value('purchase_no');

        if ($lastSerial) {
            $parts = explode('-', $lastSerial);
            $lastNumber = (int) $parts[2];
            $newNumber = str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return "PO-{$year}-{$newNumber}";
    }
}

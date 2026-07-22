<?php

namespace App\Services;

use App\Models\InventoryTransaction;

class InventorySerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('INV', InventoryTransaction::class, 'transaction_no');
    }
}

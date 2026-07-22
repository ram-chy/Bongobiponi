<?php

namespace App\Services;

use App\Models\ReceiveOrder;

class ReceiveOrderSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('RO', ReceiveOrder::class, 'order_no');
    }
}

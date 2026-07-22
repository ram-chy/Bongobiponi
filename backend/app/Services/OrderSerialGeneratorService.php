<?php

namespace App\Services;

use App\Models\Order;

class OrderSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('GG', Order::class, 'order_serial');
    }
}

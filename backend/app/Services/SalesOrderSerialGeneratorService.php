<?php

namespace App\Services;

use App\Models\SalesOrder;

class SalesOrderSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('GGSO', SalesOrder::class, 'sales_order_serial');
    }
}

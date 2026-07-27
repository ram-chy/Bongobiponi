<?php

namespace App\Services;

use App\Models\SalesOrder;

class SalesOrderSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('BBSO', SalesOrder::class, 'sales_order_serial');
    }
}

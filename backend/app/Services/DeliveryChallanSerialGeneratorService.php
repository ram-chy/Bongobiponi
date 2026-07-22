<?php

namespace App\Services;

use App\Models\DeliveryChallan;

class DeliveryChallanSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('GGDC', DeliveryChallan::class, 'serial');
    }
}

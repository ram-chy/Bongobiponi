<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('GGINV', Invoice::class, 'serial');
    }
}

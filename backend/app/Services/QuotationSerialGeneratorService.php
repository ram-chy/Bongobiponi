<?php

namespace App\Services;

use App\Models\Quotation;

class QuotationSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('BBQ', Quotation::class, 'quotation_serial');
    }
}

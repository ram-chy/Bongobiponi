<?php

namespace App\Services;

use App\Models\Payment;

class PaymentSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('GGPAY', Payment::class, 'payment_no');
    }
}

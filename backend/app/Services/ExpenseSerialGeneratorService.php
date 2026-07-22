<?php

namespace App\Services;

use App\Models\Expense;

class ExpenseSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('GGEXP', Expense::class, 'expense_no');
    }
}

<?php

namespace App\Services;

use App\Models\Expense;

class ExpenseSerialGeneratorService extends SerialGeneratorService
{
    public function __construct()
    {
        parent::__construct('BBEXP', Expense::class, 'expense_no');
    }
}

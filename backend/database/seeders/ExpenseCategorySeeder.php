<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        ExpenseCategory::firstOrCreate(['name' => 'Office Rent']);
        ExpenseCategory::firstOrCreate(['name' => 'Electricity']);
        ExpenseCategory::firstOrCreate(['name' => 'Internet']);
        ExpenseCategory::firstOrCreate(['name' => 'Fuel']);
        ExpenseCategory::firstOrCreate(['name' => 'Printing']);
        ExpenseCategory::firstOrCreate(['name' => 'Salary']);
        ExpenseCategory::firstOrCreate(['name' => 'Stationery']);
        ExpenseCategory::firstOrCreate(['name' => 'Marketing']);
        ExpenseCategory::firstOrCreate(['name' => 'Miscellaneous']);
    }
}

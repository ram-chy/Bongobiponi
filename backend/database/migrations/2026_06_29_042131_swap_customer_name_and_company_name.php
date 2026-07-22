<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Customer::cursor() as $customer) {
            $temp = $customer->name;
            $customer->name = $customer->company_name;
            $customer->company_name = $temp;
            $customer->save();
        }
    }

    public function down(): void
    {
        foreach (Customer::cursor() as $customer) {
            $temp = $customer->name;
            $customer->name = $customer->company_name;
            $customer->company_name = $temp;
            $customer->save();
        }
    }
};

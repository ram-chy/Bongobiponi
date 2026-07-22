<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();
        $customer = Customer::first() ?? Customer::factory()->create(['created_by' => $user->id]);

        Quotation::factory()
            ->count(10)
            ->withItems(3)
            ->create([
                'created_by' => $user->id,
                'customer_id' => $customer->id,
            ]);
    }
}

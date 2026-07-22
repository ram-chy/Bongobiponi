<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SalesOrder> */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'document_reference_uuid' => (string) Str::uuid(),
            'sales_order_serial' => 'GGSO/' . str_pad((string) $counter, 3, '0', STR_PAD_LEFT) . '/' . now()->format('y'),
            'customer_id' => Customer::factory(),
            'sales_order_source' => 'single',
            'sales_order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->addDays(7)->format('Y-m-d'),
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 0,
            'currency' => 'INR',
            'exchange_rate' => 1,
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function withItems(int $count = 2): static
    {
        return $this->has(SalesOrderItemFactory::new()->count($count), 'items');
    }
}

<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'order_serial' => 'GG/' . str_pad((string) $counter, 3, '0', STR_PAD_LEFT) . '/' . now()->format('y'),
            'customer_id' => Customer::factory(),
            'order_source' => 'manual',
            'order_date' => now()->format('Y-m-d'),
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
        return $this->has(OrderItemFactory::new()->count($count), 'items');
    }
}

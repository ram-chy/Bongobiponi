<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_no' => 'BBPAY/' . fake()->unique()->numerify('###') . '/' . now()->format('y'),
            'customer_id' => Customer::factory(),
            'payment_date' => fake()->date(),
            'payment_method' => fake()->randomElement(['Cash', 'Bank Transfer', 'UPI', 'Cheque']),
            'reference_no' => fake()->optional()->numerify('REF-####'),
            'remarks' => fake()->optional()->sentence(),
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}

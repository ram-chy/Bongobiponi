<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'customer_code' => 'GGCU/' . fake()->unique()->numerify('###'),
            'name' => fake()->name(),
            'company_name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'alternate_phone' => fake()->optional()->phoneNumber(),
            'gst_number' => fake()->optional()->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}'),
            'pan_number' => fake()->optional()->regexify('[A-Z]{5}[0-9]{4}[A-Z]{1}'),
            'billing_address' => fake()->address(),
            'shipping_address' => fake()->optional()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'credit_limit' => fake()->randomFloat(2, 0, 100000),
            'opening_balance' => fake()->randomFloat(2, 0, 50000),
            'status' => fake()->randomElement(['active', 'inactive']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

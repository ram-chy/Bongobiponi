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
            'customer_code' => 'BBCU/' . fake()->unique()->numerify('###'),
            'name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'alternate_phone' => fake()->optional()->phoneNumber(),
            'billing_address' => fake()->address(),
            'shipping_address' => fake()->optional()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

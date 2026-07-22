<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'name' => fake()->name(),
            'company_name' => fake()->company(),
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'gst_number' => fake()->optional(0.7)->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}'),
            'address' => fake()->address(),
            'remarks' => fake()->optional()->sentence(),
            'status' => fake()->boolean(85),
        ];
    }
}

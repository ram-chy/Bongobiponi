<?php

namespace Database\Factories;

use App\Models\Publisher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Publisher> */
class PublisherFactory extends Factory
{
    protected $model = Publisher::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'name' => fake()->company() . ' Publishing',
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'email' => fake()->optional(0.8)->companyEmail(),
            'address' => fake()->address(),
            'remarks' => fake()->optional()->sentence(),
            'status' => fake()->boolean(90),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'parent_id' => null,
            'name' => fake()->unique()->word(),
            'description' => fake()->optional(0.7)->sentence(),
            'status' => fake()->boolean(90),
        ];
    }
}

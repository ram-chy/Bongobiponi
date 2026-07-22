<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Author> */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'name' => fake()->name(),
            'biography' => fake()->optional(0.6)->paragraphs(2, true),
            'country' => fake()->optional(0.8)->country(),
            'remarks' => fake()->optional()->sentence(),
            'status' => fake()->boolean(90),
        ];
    }
}

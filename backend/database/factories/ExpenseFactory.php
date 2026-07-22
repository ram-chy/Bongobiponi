<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Expense> */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'expense_no' => 'GGEXP/' . fake()->unique()->numerify('###') . '/' . now()->format('y'),
            'expense_date' => fake()->date(),
            'category_id' => ExpenseCategory::factory(),
            'payment_method' => fake()->randomElement(['Cash', 'Bank Transfer', 'UPI', 'Cheque']),
            'reference_no' => fake()->optional()->numerify('REF-####'),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'vendor_name' => fake()->company(),
            'remarks' => fake()->optional()->sentence(),
            'attachment' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}

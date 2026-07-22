<?php

namespace Database\Factories;

use App\Models\QuotationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuotationItem> */
class QuotationItemFactory extends Factory
{
    protected $model = QuotationItem::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(2, 10, 5000);
        $discountPercentage = fake()->optional(0.3)->randomFloat(2, 0, 20) ?? 0;
        $baseAmount = $quantity * $unitPrice;
        $discountAmount = $baseAmount * ($discountPercentage / 100);
        $taxPercentage = fake()->optional(0.5)->randomFloat(2, 0, 18) ?? 0;
        $taxAmount = ($baseAmount - $discountAmount) * ($taxPercentage / 100);
        $lineTotal = $baseAmount - $discountAmount + $taxAmount;

        return [
            'item_no' => 1,
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['pcs', 'kg', 'm', 'sqft', 'hours', 'days']),
            'unit_price' => $unitPrice,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'tax_percentage' => $taxPercentage,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
            'remarks' => fake()->optional()->sentence(),
            'sort_order' => 0,
            'is_converted' => false,
            'remaining_quantity' => $quantity,
        ];
    }
}

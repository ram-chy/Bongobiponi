<?php

namespace Database\Factories;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $orderedQuantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(2, 10, 5000);
        $discountPercentage = fake()->optional(0.3)->randomFloat(2, 0, 20) ?? 0;
        $baseAmount = $orderedQuantity * $unitPrice;
        $discountAmount = $baseAmount * ($discountPercentage / 100);
        $taxPercentage = fake()->optional(0.5)->randomFloat(2, 0, 18) ?? 0;
        $taxAmount = ($baseAmount - $discountAmount) * ($taxPercentage / 100);
        $lineTotal = $baseAmount - $discountAmount + $taxAmount;

        return [
            'source_type' => 'manual',
            'item_no' => 1,
            'description' => fake()->sentence(3),
            'unit' => fake()->randomElement(['pcs', 'kg', 'm', 'sqft', 'hours', 'days']),
            'ordered_quantity' => $orderedQuantity,
            'remaining_order_quantity' => $orderedQuantity,
            'unit_price' => $unitPrice,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'tax_percentage' => $taxPercentage,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
            'sort_order' => 0,
        ];
    }
}

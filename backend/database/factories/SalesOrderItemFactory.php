<?php

namespace Database\Factories;

use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesOrderItem> */
class SalesOrderItemFactory extends Factory
{
    protected $model = SalesOrderItem::class;

    public function definition(): array
    {
        $salesOrderQuantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(2, 10, 5000);
        $discountPercentage = fake()->optional(0.3)->randomFloat(2, 0, 20) ?? 0;
        $baseAmount = $salesOrderQuantity * $unitPrice;
        $discountAmount = $baseAmount * ($discountPercentage / 100);
        $taxPercentage = fake()->optional(0.5)->randomFloat(2, 0, 18) ?? 0;
        $taxAmount = ($baseAmount - $discountAmount) * ($taxPercentage / 100);
        $lineTotal = $baseAmount - $discountAmount + $taxAmount;

        return [
            'source_type' => 'order',
            'item_no' => 1,
            'description' => fake()->sentence(3),
            'unit' => fake()->randomElement(['pcs', 'kg', 'm', 'sqft', 'hours', 'days']),
            'ordered_quantity' => $salesOrderQuantity,
            'sales_order_quantity' => $salesOrderQuantity,
            'remaining_sales_quantity' => $salesOrderQuantity,
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

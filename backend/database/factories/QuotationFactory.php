<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Quotation> */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'quotation_serial' => 'GGQ/' . str_pad((string) $counter, 3, '0', STR_PAD_LEFT) . '/' . now()->format('y'),
            'customer_id' => Customer::factory(),
            'quotation_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(15)->format('Y-m-d'),
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 0,
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function withItems(int $count = 2): static
    {
        return $this->has(QuotationItemFactory::new()->count($count), 'items');
    }
}

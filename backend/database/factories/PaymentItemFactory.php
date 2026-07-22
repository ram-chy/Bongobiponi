<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentItem> */
class PaymentItemFactory extends Factory
{
    protected $model = PaymentItem::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'invoice_id' => Invoice::factory(),
            'paid_amount' => fake()->randomFloat(2, 100, 5000),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}

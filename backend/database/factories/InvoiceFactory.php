<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'serial' => 'GGINV/' . fake()->unique()->numerify('###') . '/' . now()->format('y'),
            'invoice_date' => fake()->date(),
            'due_date' => fake()->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'customer_id' => Customer::factory(),
            'billing_address' => fake()->address(),
            'subtotal' => fake()->randomFloat(2, 1000, 10000),
            'discount_amount' => 0,
            'tax_amount' => 0,
            'round_off' => 0,
            'grand_total' => 0,
            'paid_amount' => 0,
            'payment_status' => 'Unpaid',
            'status' => 'issued',
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Invoice $invoice) {
            if ($invoice->grand_total === 0) {
                $invoice->grand_total = $invoice->subtotal;
            }
        });
    }
}
